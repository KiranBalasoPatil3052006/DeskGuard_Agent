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
     * Retrieve a summary of key metrics for a given company.
     *
     * The returned array contains:
     * - total_machines       (int)
     * - online               (int)
     * - offline              (int)
     * - critical_alerts      (int)
     * - pending_updates      (int)
     * - usb_events_today     (int)
     *
     * @param int $companyId The company ID.
     * @return array An associative array of summary metrics.
     */
    public function getCompanySummary(int $companyId): array
    {
        try {
            $totalMachines = $this->machine->where('company_id', '=', $companyId)->count();
            $online        = $this->machine->where('company_id', '=', $companyId)->where('is_online', '=', true)->count();
            $offline       = $this->machine->where('company_id', '=', $companyId)->where('is_online', '=', false)->count();

            $criticalAlerts = $this->alert
                ->whereHas('machine', function ($query) use ($companyId) {
                    $query->where('company_id', '=', $companyId);
                })
                ->where('severity', '=', 'critical')
                ->whereNull('resolved_at')
                ->count();

            $pendingUpdates = $this->machine
                ->where('company_id', '=', $companyId)
                ->where('is_online', '=', true)
                ->whereHas('windowsUpdates', function ($query) {
                    $query->where('is_installed', false);
                })
                ->count();

            $usbEventsToday = \App\Models\UsbActivity::whereHas('machine', function ($query) use ($companyId) {
                $query->where('company_id', '=', $companyId);
            })->whereDate('created_at', '=', today())->count();

            return [
                'total_machines'   => $totalMachines,
                'online'           => $online,
                'offline'          => $offline,
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
                ->whereHas('machine', function ($query) use ($companyId) {
                    $query->where('company_id', '=', $companyId);
                })
                ->where('created_at', '>=', $from)
                ->orderBy('created_at', 'asc')
                ->get(['machine_id', 'created_at', 'cpu_percentage', 'cpu_temperature']);
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
                ->whereHas('machine', function ($query) use ($companyId) {
                    $query->where('company_id', '=', $companyId);
                })
                ->where('created_at', '>=', $from)
                ->orderBy('created_at', 'asc')
                ->get(['machine_id', 'created_at', 'ram_percentage', 'ram_used_bytes', 'ram_total_bytes']);
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
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total, severity')
                ->whereHas('machine', function ($query) use ($companyId) {
                    $query->where('company_id', '=', $companyId);
                })
                ->where('created_at', '>=', $from)
                ->groupByRaw('DATE(created_at), severity')
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
