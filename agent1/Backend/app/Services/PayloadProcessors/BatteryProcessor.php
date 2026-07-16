<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\MachineCurrentStatus;
use App\Models\HealthLog;
use Illuminate\Support\Facades\Log;

class BatteryProcessor
{
    /**
     * Process battery data from the agent payload.
     *
     * @param Machine        $machine   The machine being processed.
     * @param array          $payload   The normalised payload data.
     * @param HealthLog|null $healthLog Shared HealthLog row created by PayloadProcessorService.
     */
    public function process(Machine $machine, array $payload, ?HealthLog $healthLog = null): void
    {
        try {
            $battery = $payload['battery'] ?? [];
            if (empty($battery)) {
                return;
            }

            $percentage = $battery['batteryPercentage'] ?? $battery['battery_percentage'] ?? null;
            $isCharging = $battery['isCharging'] ?? $battery['is_charging'] ?? null;
            $runTime    = $battery['estimatedRunTimeSeconds'] ?? $battery['estimated_run_time_seconds'] ?? null;
            $wearLevel  = $battery['wearLevelPercentage'] ?? $battery['wear_level_percentage'] ?? null;
            $chemistry  = $battery['chemistry'] ?? null;

            $isPresent    = $battery['isBatteryPresent'] ?? $battery['is_battery_present'] ?? null;
            $designCap    = $battery['designCapacity'] ?? $battery['design_capacity'] ?? null;
            $fullCharge   = $battery['fullChargeCapacity'] ?? $battery['full_charge_capacity'] ?? null;

            MachineCurrentStatus::updateOrCreate(
                ['machine_id' => $machine->id],
                [
                    'battery_percentage'          => $percentage,
                    'battery_charging_status'     => $isCharging,
                    'battery_wear_level'          => $wearLevel,
                    'battery_is_present'          => $isPresent,
                    'battery_design_capacity'     => $designCap,
                    'battery_full_charge_capacity' => $fullCharge,
                    'collected_at'                => now(),
                ]
            );

            // Update the shared HealthLog row instead of creating a duplicate.
            if ($healthLog) {
                $healthLog->update([
                    'battery_percentage' => $percentage,
                ]);
            }

            Log::debug('BatteryProcessor: Processed battery data', [
                'machine_id' => $machine->id,
                'percentage' => $percentage,
            ]);
        } catch (\Throwable $e) {
            Log::error('BatteryProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
