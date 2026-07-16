<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\EventLog;
use Illuminate\Support\Facades\Log;

class EventLogProcessor
{
    private const ALLOWED_LEVELS = ['Error', 'Warning', 'Critical'];

    public function process(Machine $machine, array $payload): void
    {
        try {
            $events = $payload['eventLogs'] ?? $payload['event_logs'] ?? [];
            if (empty($events)) {
                return;
            }

            $inserted = 0;
            $existingHashes = [];
            $recentEvents = EventLog::where('machine_id', $machine->id)
                ->where('collected_at', '>=', now()->subDay())
                ->get()
                ->keyBy(function ($e) {
                    return md5($e->event_id . '|' . $e->log_name . '|' . $e->source . '|' . $e->event_time);
                });

            foreach ($events as $event) {
                $level = $event['level'] ?? 'Information';
                if (!in_array($level, self::ALLOWED_LEVELS, true)) {
                    continue;
                }

                $eventId   = $event['eventId'] ?? $event['event_id'] ?? null;
                $logName   = $event['logName'] ?? $event['log_name'] ?? $event['source'] ?? null;
                $source    = $event['source'] ?? $event['providerName'] ?? null;
                $message   = $event['message'] ?? null;
                $eventTime = $event['timeGenerated'] ?? $event['time_generated'] ?? $event['loggedAt'] ?? now();

                $eventTimeStr = is_string($eventTime) ? $eventTime : now();
                $hash = md5(($eventId ?? '') . '|' . ($logName ?? '') . '|' . ($source ?? '') . '|' . $eventTimeStr);
                if (isset($existingHashes[$hash])) {
                    continue;
                }
                $existingHashes[$hash] = true;

                EventLog::create([
                    'company_id'   => $machine->company_id,
                    'machine_id'   => $machine->id,
                    'event_id'     => $eventId !== null ? (int)$eventId : null,
                    'log_name'     => $logName,
                    'source'       => $source,
                    'level'        => $level,
                    'message'      => $message,
                    'event_time'   => $eventTimeStr,
                    'collected_at' => now(),
                ]);

                $inserted++;
            }

            Log::debug('EventLogProcessor: Processed event logs', [
                'machine_id' => $machine->id,
                'inserted'   => $inserted,
            ]);
        } catch (\Throwable $e) {
            Log::error('EventLogProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
