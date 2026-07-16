<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use Illuminate\Support\Facades\Log;

class MachineProcessor
{
    public function process(Machine $machine, array $payload): void
    {
        try {
            $systemInfo = $payload['systemInfo'] ?? [];
            $cpu = $payload['cpu'] ?? [];
            $memory = $payload['memory'] ?? [];

            $totalMemoryBytes = $memory['totalMemoryBytes'] ?? $memory['total_memory_bytes'] ?? null;
            $ramGb = $totalMemoryBytes !== null ? (int)round($totalMemoryBytes / 1073741824, 0) : null;

            $machine->update([
                'hostname'               => $payload['computerName'] ?? $systemInfo['computerName'] ?? $machine->hostname,
                'device_name'            => $payload['computerName'] ?? $systemInfo['computerName'] ?? $machine->device_name,
                'domain_name'            => $systemInfo['domainName'] ?? $systemInfo['domain_name'] ?? null,
                'architecture'           => $systemInfo['architecture'] ?? null,
                'operating_system'       => $systemInfo['operatingSystem'] ?? $systemInfo['operating_system'] ?? $machine->operating_system,
                'os_version'             => $systemInfo['osVersion'] ?? $systemInfo['os_version'] ?? null,
                'uptime_seconds'         => $systemInfo['uptimeSeconds'] ?? $systemInfo['systemUptime'] ?? $systemInfo['uptime_seconds'] ?? null,
                'current_logged_in_users' => $systemInfo['currentLoggedInUsers'] ?? $systemInfo['current_logged_in_users'] ?? null,
                'processor'              => $cpu['processorName'] ?? $cpu['processor_name'] ?? null,
                'ram_gb'                 => $ramGb,
                'is_online'              => true,
                'last_heartbeat_at'      => now(),
            ]);

            \App\Models\MachineCurrentStatus::updateOrCreate(
                ['machine_id' => $machine->id],
                [
                    'company_id'        => $machine->company_id,
                    'last_collected_at' => now(),
                    'collected_at'      => now(),
                ]
            );

            Log::info('MachineProcessor: Machine updated', [
                'machine_id'  => $machine->id,
                'machine_uid' => $machine->machine_uid,
            ]);
        } catch (\Throwable $e) {
            Log::error('MachineProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
