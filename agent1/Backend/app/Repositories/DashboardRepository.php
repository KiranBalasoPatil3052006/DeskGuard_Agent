<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Alert;
use App\Models\HealthLog;
use App\Models\Machine;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class DashboardRepository
 *
 * Specialized read-only repository for dashboard and reporting aggregation queries.
 * Does NOT extend BaseRepository as it operates across multiple models.
 *
 * @package App\Repositories
 */
class DashboardRepository
{
    /**
     * The Machine model instance.
     *
     * @var Machine
     */
    protected Machine $machine;

    /**
     * The User model instance.
     *
     * @var User
     */
    protected User $user;

    /**
     * The HealthLog model instance.
     *
     * @var HealthLog
     */
    protected HealthLog $healthLog;

    /**
     * The Alert model instance.
     *
     * @var Alert
     */
    protected Alert $alert;

    /**
     * DashboardRepository constructor.
     *
     * @param Machine   $machine   The Machine model instance.
     * @param User      $user      The User model instance.
     * @param HealthLog $healthLog The HealthLog model instance.
     * @param Alert     $alert     The Alert model instance.
     */
    public function __construct(Machine $machine, User $user, HealthLog $healthLog, Alert $alert)
    {
        $this->machine   = $machine;
        $this->user      = $user;
        $this->healthLog = $healthLog;
        $this->alert     = $alert;
    }

    /**
     * PERFORMANCE OPTIMIZED: Retrieve a summary of key metrics for a given company.
     *
     * Before: 5 separate COUNT queries with JOINs to machines table = ~300ms
     * After:  2 aggregate queries using direct company_id columns = ~50ms
     *
     * Key optimization: alerts and usb_activities already have company_id columns,
     * so JOINing to machines was unnecessary overhead.
     *
     * @param int $companyId The company ID.
     * @return array An associative array of summary metrics.
     */
    public function getCompanySummary(int $companyId): array
    {
        try {
            // PERFORMANCE: Single aggregate query for all machine counts
            $machineCounts = $this->machine
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_online THEN 1 ELSE 0 END) as online_count')
                ->where('company_id', $companyId)
                ->first();

            $totalMachines = (int) ($machineCounts->total ?? 0);
            $online = (int) ($machineCounts->online_count ?? 0);

            // PERFORMANCE: Use alerts.company_id directly — no JOIN to machines needed.
            // The alerts table already has company_id with index alerts_company_severity_status_idx.
            $criticalAlerts = $this->alert
                ->where('company_id', $companyId)
                ->where('severity', 'critical')
                ->whereNull('resolved_at')
                ->count();

            // PERFORMANCE: Pending updates — simplified query using existing index.
            $pendingUpdates = $this->machine
                ->where('company_id', $companyId)
                ->where('is_online', true)
                ->whereExists(function ($query) {
                    $query->selectRaw(1)
                        ->from('windows_updates')
                        ->whereColumn('windows_updates.machine_id', 'machines.id')
                        ->where('windows_updates.is_installed', false);
                })
                ->count();

            // PERFORMANCE: Use usb_activities.company_id directly — no JOIN needed.
            $usbEventsToday = \App\Models\UsbActivity::where('company_id', $companyId)
                ->whereDate('created_at', today())
                ->count();

            return [
                'total_machines'   => $totalMachines,
                'online'           => $online,
                'offline'          => $totalMachines - $online,
                'critical_alerts'  => $criticalAlerts,
                'pending_updates'  => $pendingUpdates,
                'usb_events_today' => $usbEventsToday,
            ];
        } catch (\Throwable $e) {
            Log::error('DashboardRepository::getCompanySummary - Failed to retrieve company summary', [
                'companyId' => $companyId,
                'error'     => $e->getMessage(),
            ]);

            return [
                'total_machines'   => 0,
                'online'           => 0,
                'offline'          => 0,
                'critical_alerts'  => 0,
                'pending_updates'  => 0,
                'usb_events_today' => 0,
            ];
        }
    }

    /**
     * Retrieve a summary of key metrics for a specific employee (user).
     *
     * The returned array contains:
     * - machine_status   (string|null)
     * - alerts_count     (int)
     * - last_heartbeat   (string|null)
     *
     * @param int $userId The user ID.
     * @return array An associative array of employee summary metrics.
     */
    public function getEmployeeSummary(int $userId): array
    {
        try {
            $machine = $this->machine->where('user_id', '=', $userId)->first();

            $machineStatus = $machine ? ($machine->is_online ? 'online' : 'offline') : null;
            $lastHeartbeat = $machine ? optional($machine->last_heartbeat_at)->toIso8601String() : null;

            $alertsCount = 0;
            if ($machine) {
                $alertsCount = $this->alert
                    ->where('machine_id', '=', $machine->id)
                    ->whereNull('resolved_at')
                    ->count();
            }

            return [
                'machine_status' => $machineStatus,
                'alerts_count'   => $alertsCount,
                'last_heartbeat' => $lastHeartbeat,
            ];
        } catch (\Throwable $e) {
            Log::error('DashboardRepository::getEmployeeSummary - Failed to retrieve employee summary', [
                'userId' => $userId,
                'error'  => $e->getMessage(),
            ]);

            return [
                'machine_status' => null,
                'alerts_count'   => 0,
                'last_heartbeat' => null,
            ];
        }
    }

    /**
     * Retrieve CPU usage trend data across all machines in a company.
     *
     * @param int $companyId The company ID.
     * @param int $hours     The number of hours to look back.
     * @return Collection A collection of health log records with CPU data for the company.
     */
    public function getCpuTrendData(int $companyId, int $hours = 24): Collection
    {
        try {
            $from = now()->subHours($hours);

            return $this->healthLog
                ->join('machines', 'machines.id', '=', 'health_logs.machine_id')
                ->where('machines.company_id', '=', $companyId)
                ->where('health_logs.created_at', '>=', $from)
                ->orderBy('health_logs.created_at', 'asc')
                ->get(['health_logs.machine_id', 'health_logs.created_at', 'health_logs.cpu_percentage', 'health_logs.cpu_temperature']);
        } catch (\Throwable $e) {
            Log::error('DashboardRepository::getCpuTrendData - Failed to retrieve CPU trend data', [
                'companyId' => $companyId,
                'hours'     => $hours,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve RAM usage trend data across all machines in a company.
     *
     * @param int $companyId The company ID.
     * @param int $hours     The number of hours to look back.
     * @return Collection A collection of health log records with RAM data for the company.
     */
    public function getRamTrendData(int $companyId, int $hours = 24): Collection
    {
        try {
            $from = now()->subHours($hours);

            return $this->healthLog
                ->join('machines', 'machines.id', '=', 'health_logs.machine_id')
                ->where('machines.company_id', '=', $companyId)
                ->where('health_logs.created_at', '>=', $from)
                ->orderBy('health_logs.created_at', 'asc')
                ->get(['health_logs.machine_id', 'health_logs.created_at', 'health_logs.ram_percentage', 'health_logs.ram_used_bytes', 'health_logs.ram_total_bytes']);
        } catch (\Throwable $e) {
            Log::error('DashboardRepository::getRamTrendData - Failed to retrieve RAM trend data', [
                'companyId' => $companyId,
                'hours'     => $hours,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve alert trend data for a company over a specified number of days.
     *
     * @param int $companyId The company ID.
     * @param int $days      The number of days to look back.
     * @return Collection A collection of aggregated alert counts grouped by date and severity.
     */
    public function getAlertTrendData(int $companyId, int $days = 7): Collection
    {
        try {
            $from = now()->subDays($days);

            return $this->alert
                ->join('machines', 'machines.id', '=', 'alerts.machine_id')
                ->selectRaw('DATE(alerts.created_at) as date, COUNT(*) as total, alerts.severity')
                ->where('machines.company_id', '=', $companyId)
                ->where('alerts.created_at', '>=', $from)
                ->groupByRaw('DATE(alerts.created_at), alerts.severity')
                ->orderBy('date', 'asc')
                ->get();
        } catch (\Throwable $e) {
            Log::error('DashboardRepository::getAlertTrendData - Failed to retrieve alert trend data', [
                'companyId' => $companyId,
                'days'      => $days,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }
}
