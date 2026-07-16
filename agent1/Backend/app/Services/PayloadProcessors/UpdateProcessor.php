<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\WindowsUpdate;
use App\Models\MachineCurrentStatus;
use Illuminate\Support\Facades\Log;

class UpdateProcessor
{
    public function process(Machine $machine, array $payload): void
    {
        try {
            $updates = $payload['windowsUpdates'] ?? $payload['windows_updates'] ?? $payload['updates'] ?? [];
            if (empty($updates)) {
                return;
            }

            $pendingCount = 0;

            $isSummary = isset($updates['pendingUpdateCount']) || isset($updates['pending_update_count']);
            if ($isSummary) {
                $pendingCount   = $updates['pendingUpdateCount'] ?? $updates['pending_update_count'] ?? 0;
                $secCount       = $updates['pendingSecurityUpdateCount'] ?? $updates['pending_security_update_count'] ?? null;
                $lastInstall    = $updates['lastInstallationDate'] ?? $updates['last_installation_date'] ?? null;
                $isUpToDate     = $updates['isUpToDate'] ?? $updates['is_up_to_date'] ?? null;

                $title = 'Pending Windows Updates';
                $severity = $secCount > 0 ? 'Critical' : ($pendingCount > 0 ? 'Warning' : 'Information');
                $category = $secCount > 0 ? 'SecurityUpdates' : 'OtherUpdates';
                $description = $lastInstall
                    ? 'Last installed: ' . (is_string($lastInstall) ? substr($lastInstall, 0, 10) : $lastInstall)
                    : ($pendingCount > 0 ? "{$pendingCount} pending update(s) detected" : 'No pending updates');
                $installed = ($isUpToDate !== null) ? (bool)$isUpToDate : false;

                WindowsUpdate::updateOrCreate(
                    [
                        'machine_id'   => $machine->id,
                        'update_title' => $title,
                    ],
                    [
                        'company_id'         => $machine->company_id,
                        'update_description' => $description,
                        'category'           => $category,
                        'severity'           => $severity,
                        'is_installed'       => $installed,
                        'collected_at'       => now(),
                    ]
                );
            } else {
                foreach ($updates as $update) {
                    $title       = $update['title'] ?? $update['name'] ?? $update['updateName'] ?? 'Unknown';
                    $description = $update['description'] ?? $update['updateDescription'] ?? 'Pending update';
                    $category    = $update['category'] ?? $update['categoryName'] ?? 'OtherUpdates';
                    $severity    = $update['severity'] ?? 'Information';
                    $isPending   = $update['isPending'] ?? $update['is_pending'] ?? true;

                    WindowsUpdate::create([
                        'company_id'         => $machine->company_id,
                        'machine_id'         => $machine->id,
                        'update_title'       => $title,
                        'update_description' => $description,
                        'kb_article'         => $update['kbId'] ?? $update['kb_id'] ?? $update['kbArticle'] ?? $update['kb_article'] ?? null,
                        'category'           => $category,
                        'severity'           => $severity,
                        'is_installed'       => !$isPending,
                        'collected_at'       => now(),
                    ]);

                    if ($isPending) {
                        $pendingCount++;
                    }
                }
            }

            MachineCurrentStatus::updateOrCreate(
                ['machine_id' => $machine->id],
                [
                    'pending_updates' => $pendingCount,
                    'collected_at'    => now(),
                ]
            );

            Log::debug('UpdateProcessor: Processed update data', [
                'machine_id' => $machine->id,
                'pending'    => $pendingCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('UpdateProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
