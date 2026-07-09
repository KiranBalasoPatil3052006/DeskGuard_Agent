<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\MachineDisk;
use App\Models\MachineCurrentStatus;
use App\Models\HealthLog;
use Illuminate\Support\Facades\Log;

class DiskProcessor
{
    /**
     * Process disk data from the agent payload.
     * Updates MachineDisk records, MachineCurrentStatus, and the shared HealthLog row.
     *
     * @param Machine        $machine   The machine being processed.
     * @param array          $payload   The normalised payload data.
     * @param HealthLog|null $healthLog Shared HealthLog row created by PayloadProcessorService.
     */
    public function process(Machine $machine, array $payload, ?HealthLog $healthLog = null): void
    {
        try {
            $disks = $payload['disks'] ?? [];
            if (empty($disks)) {
                return;
            }

            $aggregateUsage = null;
            $aggregateFree  = null;
            $aggregateTotal = null;

            foreach ($disks as $disk) {
                $driveLetter  = $disk['driveName'] ?? $disk['drive_letter'] ?? $disk['driveLetter'] ?? 'C:';
                $totalBytes   = $disk['totalSizeBytes'] ?? $disk['total_size_bytes'] ?? null;
                $freeBytes    = $disk['freeSpaceBytes'] ?? $disk['free_space_bytes'] ?? null;
                $usedBytes    = $disk['usedSpaceBytes'] ?? $disk['used_space_bytes'] ?? null;
                $usagePercent = $disk['usagePercentage'] ?? $disk['usage_percentage'] ?? null;

                if ($usagePercent === null && $totalBytes !== null && $totalBytes > 0 && $usedBytes !== null) {
                    $usagePercent = round(($usedBytes / $totalBytes) * 100, 2);
                }

                if ($usagePercent !== null) {
                    $aggregateUsage = max($aggregateUsage ?? 0, $usagePercent);
                }
                if ($freeBytes !== null) {
                    $aggregateFree = ($aggregateFree ?? 0) + $freeBytes;
                }
                if ($totalBytes !== null) {
                    $aggregateTotal = ($aggregateTotal ?? 0) + $totalBytes;
                }

                $driveType = $disk['driveType'] ?? $disk['drive_type'] ?? null;
                $fs        = $disk['fileSystem'] ?? $disk['file_system'] ?? null;
                $smartOk   = $disk['isSmartHealthOk'] ?? $disk['is_smart_health_ok'] ?? null;
                $healthStatus = $smartOk !== null ? ($smartOk ? 'Good' : 'Bad') : 'Unknown';

                $volumeLabel = $disk['volumeLabel'] ?? $disk['volume_label'] ?? null;

                MachineDisk::updateOrCreate(
                    [
                        'machine_id'   => $machine->id,
                        'drive_letter' => $driveLetter,
                    ],
                    [
                        'total_gb'       => $totalBytes !== null ? round($totalBytes / 1073741824, 2) : null,
                        'used_gb'        => $usedBytes !== null ? round($usedBytes / 1073741824, 2) : null,
                        'free_gb'        => $freeBytes !== null ? round($freeBytes / 1073741824, 2) : null,
                        'volume_label'   => $volumeLabel,
                        'file_system'    => $fs,
                        'drive_type'     => $driveType,
                        'health_status'  => $healthStatus,
                        'updated_at'     => now(),
                    ]
                );
            }

            $aggregateUsed = null;
            $aggregateHealth = null;
            foreach ($disks as $disk) {
                $totalBytes = $disk['totalSizeBytes'] ?? $disk['total_size_bytes'] ?? null;
                $freeBytes  = $disk['freeSpaceBytes'] ?? $disk['free_space_bytes'] ?? null;
                if ($totalBytes !== null && $freeBytes !== null) {
                    $used = $totalBytes - $freeBytes;
                    $aggregateUsed = ($aggregateUsed ?? 0) + $used;
                }
                $smartOk = $disk['isSmartHealthOk'] ?? $disk['is_smart_health_ok'] ?? null;
                if ($smartOk !== null) {
                    if ($aggregateHealth === null) {
                        $aggregateHealth = $smartOk;
                    } elseif (!$smartOk) {
                        $aggregateHealth = false;
                    }
                }
            }

            $healthLabel = $aggregateHealth === null ? null : ($aggregateHealth ? 'Good' : 'Bad');

            MachineCurrentStatus::updateOrCreate(
                ['machine_id' => $machine->id],
                [
                    'disk_percentage'    => $aggregateUsage,
                    'disk_free_bytes'    => $aggregateFree,
                    'disk_total_bytes'   => $aggregateTotal,
                    'disk_used_bytes'    => $aggregateUsed,
                    'disk_health_status' => $healthLabel,
                    'collected_at'       => now(),
                ]
            );

            // Update the shared HealthLog row instead of creating a duplicate.
            if ($healthLog) {
                $healthLog->update([
                    'disk_percentage'  => $aggregateUsage,
                    'disk_free_bytes'  => $aggregateFree,
                    'disk_total_bytes' => $aggregateTotal,
                ]);
            }

            Log::debug('DiskProcessor: Processed disk data', [
                'machine_id' => $machine->id,
                'count'      => count($disks),
            ]);
        } catch (\Throwable $e) {
            Log::error('DiskProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
