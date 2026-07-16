<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\WindowsService;
use Illuminate\Support\Facades\Log;

class ServiceProcessor
{
    private const IMPORTANT_SERVICE_NAMES = [
        'WinDefend', 'wuauserv', 'Spooler', 'WdNisSvc', 'Sense',
        'MpDefender', 'SecurityHealthService', 'SysMain', 'Dnscache',
        'Dhcp', 'EventLog', 'AeLookupSvc',
    ];

    public function process(Machine $machine, array $payload): void
    {
        try {
            $services = $payload['services'] ?? [];
            if (empty($services)) {
                return;
            }

            foreach ($services as $svc) {
                $serviceName = $svc['serviceName'] ?? $svc['service_name'] ?? '';
                if (empty($serviceName)) {
                    continue;
                }

                if (!in_array($serviceName, self::IMPORTANT_SERVICE_NAMES, true)) {
                    continue;
                }

                $displayName = $svc['displayName'] ?? $svc['display_name'] ?? null;
                $status      = $svc['status'] ?? $svc['currentState'] ?? 'Unknown';
                $startType   = $svc['startType'] ?? $svc['start_type'] ?? 'Manual';

                $serviceType = $svc['serviceType'] ?? $svc['service_type'] ?? null;
                $logOnAs     = $svc['logOnAs'] ?? $svc['log_on_as'] ?? null;

                WindowsService::updateOrCreate(
                    [
                        'machine_id'   => $machine->id,
                        'service_name' => $serviceName,
                    ],
                    [
                        'company_id'   => $machine->company_id,
                        'display_name' => $displayName,
                        'status'       => $status,
                        'start_type'   => $startType,
                        'service_type' => $serviceType,
                        'log_on_as'    => $logOnAs,
                        'collected_at' => now(),
                    ]
                );
            }

            Log::debug('ServiceProcessor: Processed important services', [
                'machine_id' => $machine->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
