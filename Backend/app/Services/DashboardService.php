<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Alert;
use App\Models\HealthLog;
use App\Models\Machine;
use App\Models\MachineCurrentStatus;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Class DashboardService
 *
 * Aggregates dashboard data for company-level and employee-level views.
 * Provides chart data for CPU, RAM, and alert trends.
 *
 * @package App\Services
 */
class DashboardService
{
    /**
     * DashboardService constructor.
     */
    public function __construct()
    {
    }

    /**
     * Get the company dashboard data including summary cards and chart data.
     *
     * Returns an array with total machines, online/offline counts, total alerts,
     * critical alerts, and chart datasets.
     *
     * @param  int    $companyId
     * @return array  ['cards' => [...], 'cpu_chart' => [...], 'ram_chart' => [...], 'alert_chart' => [...]]
     */
    public function getCompanyDashboard(int $companyId): array
    {
        try {
            $totalMachines = Machine::where('company_id', $companyId)->count();
            $onlineCount = Machine::where('company_id', $companyId)->where('is_online', true)->count();
            $offlineCount = $totalMachines - $onlineCount;
            $totalAlerts = Alert::where('company_id', $companyId)->count();
            $criticalAlerts = Alert::where('company_id', $companyId)
                ->where('severity', 'critical')
                ->whereIn('status', ['open', 'acknowledged'])
                ->count();

            $cards = [
                'total_machines'   => $totalMachines,
                'online_count'     => $onlineCount,
                'offline_count'    => $offlineCount,
                'total_alerts'     => $totalAlerts,
                'critical_alerts'  => $criticalAlerts,
            ];

            $cpuChart = $this->getCpuChartData($companyId, 24);
            $ramChart = $this->getRamChartData($companyId, 24);
            $alertChart = $this->getAlertChartData($companyId, 7);

            return [
                'cards'       => $cards,
                'cpu_chart'   => $cpuChart,
                'ram_chart'   => $ramChart,
                'alert_chart' => $alertChart,
            ];
        } catch (Exception $e) {
            Log::error('DashboardService::getCompanyDashboard - Failed to build dashboard', [
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the employee dashboard showing their assigned machine data.
     *
     * @param  int     $userId
     * @return array   ['machine' => Machine|null, 'current_status' => MachineCurrentStatus|null, 'recent_alerts' => Collection]
     */
    public function getEmployeeDashboard(int $userId): array
    {
        try {
            $machine = Machine::with(['currentStatus'])
                ->where('user_id', $userId)
                ->first();

            $recentAlerts = collect();
            if ($machine) {
                $recentAlerts = Alert::where('machine_id', $machine->id)
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get();
            }

            return [
                'machine'        => $machine,
                'current_status' => $machine?->currentStatus,
                'recent_alerts'  => $recentAlerts,
            ];
        } catch (Exception $e) {
            Log::error('DashboardService::getEmployeeDashboard - Failed to build employee dashboard', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get CPU usage chart data for a company over a specified number of hours.
     *
     * Returns an array of labels and datasets for chart rendering.
     *
     * @param  int  $companyId
     * @param  int  $hours
     * @return array
     */
    public function getCpuChartData(int $companyId, int $hours = 24): array
    {
        try {
            $since = now()->subHours($hours);

            $logs = HealthLog::with('machine:id,hostname,device_name')
                ->where('company_id', $companyId)
                ->where('collected_at', '>=', $since)
                ->whereNotNull('cpu_percentage')
                ->orderBy('collected_at')
                ->get();

            $labelsSet = [];
            $machineData = [];

            foreach ($logs as $log) {
                // Use Hour:Minute to align data points across machines
                $timeLabel = $log->collected_at->format('H:i');
                $labelsSet[$timeLabel] = true;

                $machineName = $log->machine ? ($log->machine->hostname ?: $log->machine->device_name ?: "Machine {$log->machine_id}") : "Unknown";
                
                if (!isset($machineData[$machineName])) {
                    $machineData[$machineName] = [];
                }
                
                $machineData[$machineName][$timeLabel] = (float) $log->cpu_percentage;
            }

            $labels = array_keys($labelsSet);
            // Sort chronologically (assuming H:i works for < 24h, if it crosses midnight we might need a better sort, but since it's just H:i from the query, it's safer to not sort and rely on the query order).
            // Actually, since logs are ordered by collected_at, the order we encounter them is chronological.
            // Let's preserve the order they were inserted into $labelsSet.
            $labels = array_keys($labelsSet);

            $datasets = [];
            foreach ($machineData as $machineName => $dataByTime) {
                $values = [];
                foreach ($labels as $label) {
                    $values[] = $dataByTime[$label] ?? null; // null keeps the line continuous but skips the point, or use 0
                }
                $datasets[] = [
                    'label' => $machineName . ' CPU %',
                    'data'  => $values,
                ];
            }

            return [
                'labels' => $labels,
                'datasets' => empty($datasets) ? [['label' => 'No Data', 'data' => []]] : $datasets,
            ];
        } catch (Exception $e) {
            Log::error('DashboardService::getCpuChartData - Failed to get CPU chart data', [
                'company_id' => $companyId,
                'hours'      => $hours,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get RAM usage chart data for a company over a specified number of hours.
     *
     * Returns an array of labels and datasets for chart rendering.
     *
     * @param  int  $companyId
     * @param  int  $hours
     * @return array
     */
    public function getRamChartData(int $companyId, int $hours = 24): array
    {
        try {
            $since = now()->subHours($hours);

            $logs = HealthLog::with('machine:id,hostname,device_name')
                ->where('company_id', $companyId)
                ->where('collected_at', '>=', $since)
                ->whereNotNull('ram_percentage')
                ->orderBy('collected_at')
                ->get();

            $labelsSet = [];
            $machineData = [];

            foreach ($logs as $log) {
                $timeLabel = $log->collected_at->format('H:i');
                $labelsSet[$timeLabel] = true;

                $machineName = $log->machine ? ($log->machine->hostname ?: $log->machine->device_name ?: "Machine {$log->machine_id}") : "Unknown";
                
                if (!isset($machineData[$machineName])) {
                    $machineData[$machineName] = [];
                }
                
                $machineData[$machineName][$timeLabel] = (float) $log->ram_percentage;
            }

            $labels = array_keys($labelsSet);

            $datasets = [];
            foreach ($machineData as $machineName => $dataByTime) {
                $values = [];
                foreach ($labels as $label) {
                    $values[] = $dataByTime[$label] ?? null;
                }
                $datasets[] = [
                    'label' => $machineName . ' RAM %',
                    'data'  => $values,
                ];
            }

            return [
                'labels' => $labels,
                'datasets' => empty($datasets) ? [['label' => 'No Data', 'data' => []]] : $datasets,
            ];
        } catch (Exception $e) {
            Log::error('DashboardService::getRamChartData - Failed to get RAM chart data', [
                'company_id' => $companyId,
                'hours'      => $hours,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get alert trend chart data for a company over a specified number of days.
     *
     * Returns daily alert counts grouped by severity.
     *
     * @param  int  $companyId
     * @param  int  $days
     * @return array
     */
    public function getAlertChartData(int $companyId, int $days = 7): array
    {
        try {
            $since = now()->subDays($days);
            $labels = [];
            $critical = [];
            $warning = [];
            $info = [];

            for ($i = $days; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $labels[] = now()->subDays($i)->format('D M d');

                $critical[] = Alert::where('company_id', $companyId)
                    ->where('severity', 'critical')
                    ->whereDate('created_at', $date)
                    ->count();

                $warning[] = Alert::where('company_id', $companyId)
                    ->where('severity', 'warning')
                    ->whereDate('created_at', $date)
                    ->count();

                $info[] = Alert::where('company_id', $companyId)
                    ->where('severity', 'info')
                    ->whereDate('created_at', $date)
                    ->count();
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Critical',
                        'data'  => $critical,
                    ],
                    [
                        'label' => 'Warning',
                        'data'  => $warning,
                    ],
                    [
                        'label' => 'Info',
                        'data'  => $info,
                    ],
                ],
            ];
        } catch (Exception $e) {
            Log::error('DashboardService::getAlertChartData - Failed to get alert chart data', [
                'company_id' => $companyId,
                'days'       => $days,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
