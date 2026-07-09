<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\HealthDataDTO;
use App\Enums\EventType;
use App\Exceptions\MachineNotFoundException;
use App\Models\HealthLog;
use App\Models\Machine;
use App\Models\MachineCurrentStatus;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Class MonitoringService
 *
 * Processes health data received from machine agents.
 * Handles updating current status, storing health logs, and
 * triggering alert evaluation.
 *
 * @package App\Services
 */
class MonitoringService
{
    /**
     * The audit log service for recording monitoring events.
     *
     * @var AuditLogService
     */
    private AuditLogService $auditLogService;

    /**
     * The alert service for evaluating alert rules after health data processing.
     *
     * @var AlertService
     */
    private AlertService $alertService;

    /**
     * MonitoringService constructor.
     *
     * @param AuditLogService $auditLogService
     * @param AlertService    $alertService
     */
    public function __construct(AuditLogService $auditLogService, AlertService $alertService)
    {
        $this->auditLogService = $auditLogService;
        $this->alertService = $alertService;
    }

    /**
     * Process health data received from a machine agent.
     *
     * Finds the machine by UID, stores the health log, updates the current
     * status, and triggers alert evaluation.
     *
     * @param  HealthDataDTO  $dto
     * @return void
     *
     * @throws MachineNotFoundException
     */
    public function processHealthData(HealthDataDTO $dto): void
    {
        try {
            $data = $dto->toArray();
            $machineUid = $data['machine_uid'] ?? '';

            $machine = Machine::where('machine_uid', $machineUid)->first();

            if (!$machine) {
                throw new MachineNotFoundException(
                    'Machine not found with UID: ' . $machineUid,
                    404,
                    ['machine_uid' => $machineUid]
                );
            }

            $healthLog = $this->storeHealthLog($machine, $data);

            $currentStatus = $this->updateCurrentStatus($machine, $data);

            $this->alertService->evaluateMachineAlerts($machine, $currentStatus);

            $this->auditLogService->log(
                EventType::Sync->value,
                'Health data processed for machine: ' . $machine->machine_uid,
                null,
                ['health_log_id' => $healthLog->id],
                null,
                $machine
            );

            Log::info('Health data processed successfully', [
                'machine_id'     => $machine->id,
                'machine_uid'    => $machine->machine_uid,
                'health_log_id'  => $healthLog->id,
            ]);
        } catch (MachineNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('MonitoringService::processHealthData - Failed to process health data', [
                'machine_uid' => $data['machine_uid'] ?? 'unknown',
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Upsert the machine_current_status record for the given machine.
     *
     * @param  Machine  $machine
     * @param  array    $data
     * @return MachineCurrentStatus
     */
    public function updateCurrentStatus(Machine $machine, array $data): MachineCurrentStatus
    {
        try {
            $ramTotalBytes = $data['ram_total_bytes'] ?? null;
            $ramUsedBytes = $data['ram_used_bytes'] ?? null;
            $ramPercentage = null;
            if ($ramTotalBytes !== null && $ramTotalBytes > 0 && $ramUsedBytes !== null) {
                $ramPercentage = round(($ramUsedBytes / $ramTotalBytes) * 100, 2);
            }

            $diskTotalBytes = $data['disk_total_bytes'] ?? null;
            $diskFreeBytes = $data['disk_free_bytes'] ?? null;
            $diskPercentage = null;
            if ($diskTotalBytes !== null && $diskTotalBytes > 0 && $diskFreeBytes !== null) {
                $diskPercentage = round((($diskTotalBytes - $diskFreeBytes) / $diskTotalBytes) * 100, 2);
            }

            $status = MachineCurrentStatus::updateOrCreate(
                ['machine_id' => $machine->id],
                [
                    'company_id'          => $machine->company_id,
                    'cpu_percentage'      => $data['cpu_percentage'] ?? null,
                    'cpu_temperature'     => $data['cpu_temperature'] ?? null,
                    'ram_percentage'      => $ramPercentage,
                    'ram_used_bytes'      => $ramUsedBytes,
                    'ram_available_bytes' => $data['ram_available_bytes'] ?? null,
                    'ram_total_bytes'     => $ramTotalBytes,
                    'disk_percentage'     => $diskPercentage,
                    'disk_free_bytes'     => $diskFreeBytes,
                    'disk_total_bytes'    => $diskTotalBytes,
                    'battery_percentage'  => $data['battery_percentage'] ?? null,
                    'online_status'       => $data['online_status'] ?? false,
                    'collected_at'        => now(),
                ]
            );

            Log::info('Current status updated for machine', [
                'machine_id' => $machine->id,
                'cpu'        => $data['cpu_percentage'] ?? null,
                'ram'        => $ramPercentage,
                'disk'       => $diskPercentage,
            ]);

            return $status;
        } catch (Exception $e) {
            Log::error('MonitoringService::updateCurrentStatus - Failed to update current status', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Insert a new health_log record for the given machine.
     *
     * @param  Machine  $machine
     * @param  array    $data
     * @return HealthLog
     */
    public function storeHealthLog(Machine $machine, array $data): HealthLog
    {
        try {
            $healthLog = HealthLog::create([
                'company_id'          => $machine->company_id,
                'machine_id'          => $machine->id,
                'cpu_percentage'      => $data['cpu_percentage'] ?? null,
                'cpu_temperature'     => $data['cpu_temperature'] ?? null,
                'ram_percentage'      => $this->calculatePercentage(
                    $data['ram_used_bytes'] ?? null,
                    $data['ram_total_bytes'] ?? null
                ),
                'ram_used_bytes'      => $data['ram_used_bytes'] ?? null,
                'ram_available_bytes' => $data['ram_available_bytes'] ?? null,
                'ram_total_bytes'     => $data['ram_total_bytes'] ?? null,
                'disk_percentage'     => $this->calculateDiskPercentage(
                    $data['disk_free_bytes'] ?? null,
                    $data['disk_total_bytes'] ?? null
                ),
                'disk_free_bytes'     => $data['disk_free_bytes'] ?? null,
                'disk_total_bytes'    => $data['disk_total_bytes'] ?? null,
                'battery_percentage'  => $data['battery_percentage'] ?? null,
                'online_status'       => $data['online_status'] ?? false,
                'collected_at'        => now(),
            ]);

            Log::info('Health log stored', [
                'health_log_id' => $healthLog->id,
                'machine_id'    => $machine->id,
            ]);

            return $healthLog;
        } catch (Exception $e) {
            Log::error('MonitoringService::storeHealthLog - Failed to store health log', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate a percentage from used and total values.
     *
     * @param  int|null  $used
     * @param  int|null  $total
     * @return float|null
     */
    private function calculatePercentage(?int $used, ?int $total): ?float
    {
        if ($used === null || $total === null || $total <= 0) {
            return null;
        }
        return round(($used / $total) * 100, 2);
    }

    /**
     * Calculate disk usage percentage from free and total values.
     *
     * @param  int|null  $free
     * @param  int|null  $total
     * @return float|null
     */
    private function calculateDiskPercentage(?int $free, ?int $total): ?float
    {
        if ($free === null || $total === null || $total <= 0) {
            return null;
        }
        return round((($total - $free) / $total) * 100, 2);
    }
}
