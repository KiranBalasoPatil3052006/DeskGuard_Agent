<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\MachineCurrentStatus;
use App\Models\HealthLog;
use Illuminate\Support\Facades\Log;

class MemoryProcessor
{
    /**
     * Process memory data from the agent payload.
     * Updates MachineCurrentStatus and the shared HealthLog row.
     *
     * @param Machine        $machine   The machine being processed.
     * @param array          $payload   The normalised payload data.
     * @param HealthLog|null $healthLog Shared HealthLog row created by PayloadProcessorService.
     */
    public function process(Machine $machine, array $payload, ?HealthLog $healthLog = null): void
    {
        try {
            $memory = $payload['memory'] ?? [];
            if (empty($memory)) {
                return;
            }

            $totalBytes     = $memory['totalMemoryBytes'] ?? $memory['total_memory_bytes'] ?? null;
            $usedBytes      = $memory['usedMemoryBytes'] ?? $memory['used_memory_bytes'] ?? null;
            $availableBytes = $memory['availableMemoryBytes'] ?? $memory['available_memory_bytes'] ?? null;
            $usagePercentage = $memory['usagePercentage'] ?? $memory['usage_percentage'] ?? null;

            if ($usagePercentage === null && $totalBytes !== null && $totalBytes > 0 && $usedBytes !== null) {
                $usagePercentage = round(($usedBytes / $totalBytes) * 100, 2);
            }

            MachineCurrentStatus::updateOrCreate(
                ['machine_id' => $machine->id],
                [
                    'company_id'          => $machine->company_id,
                    'ram_percentage'      => $usagePercentage,
                    'ram_used_bytes'      => $usedBytes,
                    'ram_available_bytes' => $availableBytes,
                    'ram_total_bytes'     => $totalBytes,
                    'collected_at'        => now(),
                ]
            );

            // Update the shared HealthLog row instead of creating a duplicate.
            if ($healthLog) {
                $healthLog->update([
                    'ram_percentage'      => $usagePercentage,
                    'ram_used_bytes'      => $usedBytes,
                    'ram_available_bytes' => $availableBytes,
                    'ram_total_bytes'     => $totalBytes,
                ]);
            }

            Log::debug('MemoryProcessor: Processed memory data', [
                'machine_id' => $machine->id,
                'usage'      => $usagePercentage,
            ]);
        } catch (\Throwable $e) {
            Log::error('MemoryProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}

