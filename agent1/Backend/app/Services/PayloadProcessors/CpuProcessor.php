<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\MachineCurrentStatus;
use App\Models\HealthLog;
use Illuminate\Support\Facades\Log;

class CpuProcessor
{
    /**
     * Process CPU data from the agent payload.
     * Updates MachineCurrentStatus and the shared HealthLog row.
     *
     * @param Machine        $machine   The machine being processed.
     * @param array          $payload   The normalised payload data.
     * @param HealthLog|null $healthLog Shared HealthLog row created by PayloadProcessorService.
     */
    public function process(Machine $machine, array $payload, ?HealthLog $healthLog = null): void
    {
        try {
            $cpu = $payload['cpu'] ?? [];
            if (empty($cpu)) {
                return;
            }

            $usagePercentage = $cpu['usagePercentage'] ?? $cpu['usage_percentage'] ?? null;
            $temperature     = $cpu['temperatureCelsius'] ?? $cpu['temperature_celsius'] ?? null;

            $clockSpeed = $cpu['currentClockSpeedMHz'] ?? $cpu['current_clock_speed_mhz'] ?? null;
            $coreCount  = $cpu['numberOfLogicalProcessors'] ?? $cpu['number_of_logical_processors'] ?? null;

            MachineCurrentStatus::updateOrCreate(
                ['machine_id' => $machine->id],
                [
                    'company_id'      => $machine->company_id,
                    'cpu_percentage'  => $usagePercentage,
                    'cpu_temperature' => $temperature,
                    'cpu_clock_speed' => $clockSpeed,
                    'cpu_core_count'  => $coreCount,
                    'collected_at'    => now(),
                ]
            );

            // Update the shared HealthLog row (created by PayloadProcessorService)
            // instead of creating a new row — eliminates duplicate HealthLog entries.
            if ($healthLog) {
                $healthLog->update([
                    'cpu_percentage'  => $usagePercentage,
                    'cpu_temperature' => $temperature,
                ]);
            }

            Log::debug('CpuProcessor: Processed CPU data', [
                'machine_id' => $machine->id,
                'usage'      => $usagePercentage,
                'temp'       => $temperature,
            ]);
        } catch (\Throwable $e) {
            Log::error('CpuProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}

