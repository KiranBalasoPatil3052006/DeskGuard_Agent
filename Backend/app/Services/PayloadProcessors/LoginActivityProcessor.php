<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\LoginActivity;
use Illuminate\Support\Facades\Log;

class LoginActivityProcessor
{
    public function process(Machine $machine, array $payload): void
    {
        try {
            $activities = $payload['loginActivities'] ?? $payload['login_activities'] ?? [];
            if (empty($activities)) {
                return;
            }

            foreach ($activities as $activity) {
                $username  = $activity['username'] ?? $activity['userName'] ?? 'SYSTEM';
                $eventType = $activity['eventType'] ?? $activity['event_type'] ?? $activity['loginType'] ?? $activity['login_type'] ?? $activity['logonType'] ?? $activity['logon_type'] ?? match ($activity['eventId'] ?? null) {
                    4624 => 'Logon',
                    4625 => 'Failed Logon',
                    4634 => 'Logoff',
                    4647 => 'Logoff (Initiative)',
                    4778 => 'Session Reconnected',
                    4779 => 'Session Disconnected',
                    4800 => 'Workstation Locked',
                    4801 => 'Workstation Unlocked',
                    4648 => 'Logon (Explicit Credentials)',
                    4672 => 'Admin Logon (Special)',
                    default => 'unknown',
                };
                $sessionId = $activity['sessionId'] ?? $activity['session_id'] ?? $activity['logonId'] ?? $activity['logon_id'] ?? null;
                $eventTime = $activity['eventTime'] ?? $activity['event_time'] ?? $activity['timeGenerated'] ?? now();

                LoginActivity::create([
                    'company_id'   => $machine->company_id,
                    'machine_id'   => $machine->id,
                    'event_type'   => $eventType,
                    'username'     => $username,
                    'session_id'   => $sessionId,
                    'logon_time'   => is_string($eventTime) ? $eventTime : now(),
                    'collected_at' => now(),
                ]);
            }

            Log::debug('LoginActivityProcessor: Processed login activities', [
                'machine_id' => $machine->id,
                'count'      => count($activities),
            ]);
        } catch (\Throwable $e) {
            Log::error('LoginActivityProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
