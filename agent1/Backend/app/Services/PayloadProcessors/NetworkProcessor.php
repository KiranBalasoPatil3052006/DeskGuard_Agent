<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\MachineNetworkAdapter;
use Illuminate\Support\Facades\Log;

class NetworkProcessor
{
    public function process(Machine $machine, array $payload, $healthLog = null): void
    {
        try {
            $adapters = $payload['networkAdapters'] ?? $payload['network'] ?? $payload['network_adapters'] ?? [];
            if (empty($adapters)) {
                return;
            }

            foreach ($adapters as $adapter) {
                $adapterName  = $adapter['adapterName'] ?? $adapter['adapter_name'] ?? 'Unknown';
                $isConnected  = $adapter['isConnected'] ?? $adapter['is_connected'] ?? false;
                $ipV4         = $adapter['ipAddressV4'] ?? $adapter['ip_address_v4'] ?? $adapter['ipAddress'] ?? null;
                $mac          = $adapter['macAddress'] ?? $adapter['mac_address'] ?? null;
                $speed        = $adapter['connectionSpeedMbps'] ?? $adapter['connection_speed_mbps'] ?? null;
                $bytesSent    = $adapter['bytesSent'] ?? $adapter['bytes_sent'] ?? null;
                $bytesRecv    = $adapter['bytesReceived'] ?? $adapter['bytes_received'] ?? null;

                $ipV6 = $adapter['ipAddressV6'] ?? $adapter['ip_address_v6'] ?? null;
                $adapterType = $adapter['adapterType'] ?? $adapter['adapter_type'] ?? null;

                MachineNetworkAdapter::updateOrCreate(
                    [
                        'machine_id'   => $machine->id,
                        'adapter_name' => $adapterName,
                    ],
                    [
                        'ip_address'     => $ipV4,
                        'ip_address_v6'   => $ipV6,
                        'mac_address'    => $mac,
                        'adapter_type'   => $adapterType,
                        'speed'          => $speed,
                        'bytes_sent'     => $bytesSent,
                        'bytes_received' => $bytesRecv,
                        'status'         => $isConnected ? 'connected' : 'disconnected',
                        'updated_at'     => now(),
                    ]
                );
            }

            $totalSent = 0;
            $totalRecv = 0;
            foreach ($adapters as $adapter) {
                $totalSent += (int)($adapter['bytesSent'] ?? $adapter['bytes_sent'] ?? 0);
                $totalRecv += (int)($adapter['bytesReceived'] ?? $adapter['bytes_received'] ?? 0);
            }

            \App\Models\MachineCurrentStatus::updateOrCreate(
                ['machine_id' => $machine->id],
                [
                    'network_sent_bytes'     => $totalSent,
                    'network_received_bytes' => $totalRecv,
                    'collected_at'           => now(),
                ]
            );

            if ($healthLog) {
                $healthLog->update([
                    'network_sent_bytes'     => $totalSent,
                    'network_received_bytes' => $totalRecv,
                ]);
            }

            Log::debug('NetworkProcessor: Processed network data', [
                'machine_id' => $machine->id,
                'count'      => count($adapters),
            ]);
        } catch (\Throwable $e) {
            Log::error('NetworkProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
