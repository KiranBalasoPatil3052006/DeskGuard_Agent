<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\ProcessLog;
use Illuminate\Support\Facades\Log;

class ProcessProcessor
{
    public function process(Machine $machine, array $payload): void
    {
        try {
            $processes = $payload['processes'] ?? [];
            if (empty($processes)) {
                return;
            }

            $sorted = collect($processes)
                ->sortByDesc(function ($p) {
                    return $p['cpuUsagePercentage'] ?? $p['cpu_usage_percentage'] ?? $p['workingSetBytes'] ?? $p['working_set_bytes'] ?? 0;
                })
                ->take(20);

            foreach ($sorted as $proc) {
                $processName = $proc['processName'] ?? $proc['process_name'] ?? 'Unknown';
                $cpuUsage    = $proc['cpuUsagePercentage'] ?? $proc['cpu_usage_percentage'] ?? $proc['cpuUsage'] ?? null;
                $workingSet  = $proc['workingSetBytes'] ?? $proc['working_set_bytes'] ?? null;
                $memoryUsage = $workingSet !== null ? round($workingSet / 1048576, 2) : ($proc['memoryUsageMb'] ?? $proc['memory_usage_mb'] ?? null);

                $procId   = $proc['processId'] ?? $proc['process_id'] ?? null;
                $exePath  = $proc['executablePath'] ?? $proc['executable_path'] ?? null;
                $threads  = $proc['threadCount'] ?? $proc['thread_count'] ?? null;
                $user     = $proc['userName'] ?? $proc['user_name'] ?? null;

                ProcessLog::create([
                    'machine_id'      => $machine->id,
                    'process_name'    => $processName,
                    'process_id'      => $procId,
                    'executable_path' => $exePath,
                    'thread_count'    => $threads,
                    'user_name'       => $user,
                    'cpu_usage'       => $cpuUsage,
                    'memory_usage'    => $memoryUsage,
                    'collected_at'    => now(),
                ]);
            }

            Log::debug('ProcessProcessor: Processed top processes', [
                'machine_id' => $machine->id,
                'count'      => $sorted->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
