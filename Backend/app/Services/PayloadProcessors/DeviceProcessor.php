<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\MachineConnectedDevice;
use Illuminate\Support\Facades\Log;

class DeviceProcessor
{
    public function process(Machine $machine, array $payload): void
    {
        try {
            $devices = $payload['connectedDevices'] ?? $payload['connected_devices'] ?? [];
            if (empty($devices)) {
                return;
            }

            foreach ($devices as $device) {
                $deviceName = $device['deviceName'] ?? $device['device_name'] ?? 'Unknown';
                $deviceType = $device['deviceType'] ?? $device['device_type'] ?? 'Unknown';
                $manufacturer = $device['manufacturer'] ?? null;
                $connectionType = $device['connectionType'] ?? $device['connection_type'] ?? null;
                $status = $device['status'] ?? 'connected';
                $lastSeen = $device['lastSeen'] ?? $device['last_seen'] ?? now();

                MachineConnectedDevice::updateOrCreate(
                    [
                        'machine_id'  => $machine->id,
                        'device_name' => $deviceName,
                    ],
                    [
                        'device_type'     => $deviceType,
                        'manufacturer'    => $manufacturer,
                        'connection_type' => $connectionType,
                        'status'          => $status,
                        'last_seen'       => is_string($lastSeen) ? $lastSeen : now(),
                        'updated_at'      => now(),
                    ]
                );
            }

            Log::debug('DeviceProcessor: Processed connected devices', [
                'machine_id' => $machine->id,
                'count'      => count($devices),
            ]);
        } catch (\Throwable $e) {
            Log::error('DeviceProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
