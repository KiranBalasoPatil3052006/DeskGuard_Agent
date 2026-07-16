<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\DeviceEvent;
use Illuminate\Support\Facades\Log;

class DeviceEventProcessor
{
    public function process(Machine $machine, array $payload): void
    {
        try {
            $events = $payload['deviceEvents'] ?? $payload['device_events'] ?? [];
            if (empty($events)) {
                return;
            }

            foreach ($events as $event) {
                $eventType  = $event['eventType'] ?? $event['event_type'] ?? 'Unknown';
                $deviceName = $event['deviceName'] ?? $event['device_name'] ?? 'Unknown';
                $deviceType = $event['deviceType'] ?? $event['device_type'] ?? 'Unknown';
                $eventTime  = $event['eventTime'] ?? $event['event_time'] ?? now();

                DeviceEvent::create([
                    'machine_id'      => $machine->id,
                    'device_name'     => $deviceName,
                    'device_type'     => $deviceType,
                    'manufacturer'    => $event['manufacturer'] ?? null,
                    'connection_type' => $event['connectionType'] ?? $event['connection_type'] ?? null,
                    'event_type'      => $eventType,
                    'event_time'      => is_string($eventTime) ? $eventTime : now(),
                ]);
            }

            Log::debug('DeviceEventProcessor: Processed device events', [
                'machine_id' => $machine->id,
                'count'      => count($events),
            ]);
        } catch (\Throwable $e) {
            Log::error('DeviceEventProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
