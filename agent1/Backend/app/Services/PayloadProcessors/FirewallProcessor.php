<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\FirewallStatus;
use App\Models\MachineCurrentStatus;
use Illuminate\Support\Facades\Log;

class FirewallProcessor
{
    public function process(Machine $machine, array $payload): void
    {
        try {
            $firewall = $payload['firewall'] ?? [];
            if (empty($firewall)) {
                return;
            }

            $domainOn      = $firewall['isDomainFirewallEnabled'] ?? $firewall['is_domain_firewall_enabled'] ?? null;
            $privateOn     = $firewall['isPrivateFirewallEnabled'] ?? $firewall['is_private_firewall_enabled'] ?? null;
            $publicOn      = $firewall['isPublicFirewallEnabled'] ?? $firewall['is_public_firewall_enabled'] ?? null;
            $activeProfile = $firewall['activeProfile'] ?? $firewall['active_profile'] ?? null;

            $overall = ($domainOn || $privateOn || $publicOn);

            FirewallStatus::updateOrCreate(
                ['machine_id' => $machine->id],
                [
                    'company_id'   => $machine->company_id,
                    'is_enabled'   => $overall,
                    'profile_name' => $activeProfile,
                    'collected_at' => now(),
                ]
            );

            MachineCurrentStatus::updateOrCreate(
                ['machine_id' => $machine->id],
                [
                    'firewall_status' => $overall ? 'enabled' : 'disabled',
                    'collected_at'    => now(),
                ]
            );

            Log::debug('FirewallProcessor: Processed firewall data', [
                'machine_id' => $machine->id,
                'enabled'    => $overall,
            ]);
        } catch (\Throwable $e) {
            Log::error('FirewallProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
