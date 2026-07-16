<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\UsbActivity;
use Illuminate\Support\Facades\Log;

class UsbActivityProcessor
{
    public function process(Machine $machine, array $payload): void
    {
        try {
            $activities = $payload['usbActivities'] ?? $payload['usb_activities'] ?? [];
            if (empty($activities)) {
                return;
            }

            foreach ($activities as $activity) {
                $deviceName   = $activity['deviceName'] ?? $activity['device_name'] ?? 'Unknown USB Device';
                $deviceSerial = $activity['deviceSerial'] ?? $activity['device_serial'] ?? null;
                $eventType    = $activity['eventType'] ?? $activity['event_type'] ?? 'connected';
                $eventTime    = $activity['eventTime'] ?? $activity['event_time'] ?? $activity['timeGenerated'] ?? now();

                UsbActivity::create([
                    'company_id'   => $machine->company_id,
                    'machine_id'   => $machine->id,
                    'device_name'  => $deviceName,
                    'device_serial' => $deviceSerial,
                    'event_type'   => $eventType,
                    'collected_at' => is_string($eventTime) ? $eventTime : now(),
                ]);
            }

            Log::debug('UsbActivityProcessor: Processed USB activities', [
                'machine_id' => $machine->id,
                'count'      => count($activities),
            ]);
        } catch (\Throwable $e) {
            Log::error('UsbActivityProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
