<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\AntivirusStatus;
use App\Models\MachineCurrentStatus;
use Illuminate\Support\Facades\Log;

class AntivirusProcessor
{
    public function process(Machine $machine, array $payload): void
    {
        try {
            $av = $payload['antivirus'] ?? [];
            if (empty($av)) {
                return;
            }

            $displayName  = $av['displayName'] ?? $av['display_name'] ?? $av['productName'] ?? 'Unknown Antivirus';
            $rtp          = $av['isRealTimeProtectionEnabled'] ?? $av['is_real_time_protection_enabled'] ?? null;
            $sigUpToDate  = $av['isSignatureUpToDate'] ?? $av['is_signature_up_to_date'] ?? null;
            $defStatus    = $av['definitionVersion'] ?? $av['definition_version'] ?? $av['definitionStatus'] ?? $av['productVersion'] ?? 'Unknown';
            $lastSigUpdate = $av['lastSignatureUpdate'] ?? $av['last_signature_update'] ?? null;

            AntivirusStatus::updateOrCreate(
                ['machine_id' => $machine->id],
                [
                    'company_id'           => $machine->company_id,
                    'display_name'         => $displayName,
                    'is_enabled'           => (bool)($rtp ?? false),
                    'is_updated'           => (bool)($sigUpToDate ?? false),
                    'real_time_protection' => $rtp,
                    'definition_status'    => $defStatus,
                    'collected_at'         => now(),
                ]
            );

            MachineCurrentStatus::updateOrCreate(
                ['machine_id' => $machine->id],
                [
                    'antivirus_status' => ($rtp) ? 'enabled' : 'disabled',
                    'collected_at'     => now(),
                ]
            );

            Log::debug('AntivirusProcessor: Processed antivirus data', [
                'machine_id' => $machine->id,
                'product'    => $displayName,
                'enabled'    => $rtp,
            ]);
        } catch (\Throwable $e) {
            Log::error('AntivirusProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
