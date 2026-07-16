<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\SoftwareInventory;
use App\Services\BaselineService;
use App\Services\ChangeDetectionService;
use Illuminate\Support\Facades\Log;

class SoftwareInventoryProcessor
{
    private BaselineService $baselineService;
    private ChangeDetectionService $changeDetectionService;

    public function __construct(BaselineService $baselineService, ChangeDetectionService $changeDetectionService)
    {
        $this->baselineService = $baselineService;
        $this->changeDetectionService = $changeDetectionService;
    }

    public function process(Machine $machine, array $payload): void
    {
        try {
            $softwareList = $payload['softwareInventory'] ?? $payload['software_inventory'] ?? [];
            if (empty($softwareList)) {
                return;
            }

            $normalized = [];
            foreach ($softwareList as $software) {
                $name = $software['displayName'] ?? $software['display_name'] ?? $software['softwareName'] ?? null;
                if (empty($name)) {
                    continue;
                }
                $normalized[] = [
                    'name' => $name,
                    'version' => $software['displayVersion'] ?? $software['display_version'] ?? $software['version'] ?? null,
                    'publisher' => $software['publisher'] ?? null,
                    'architecture' => $software['architecture'] ?? $software['systemArchitecture'] ?? null,
                ];
            }

            if (empty($normalized)) {
                return;
            }

            $this->baselineService->syncSoftwareBaseline($machine, $normalized);
            $this->changeDetectionService->detectSoftwareChanges($machine, $normalized);

            SoftwareInventory::where('machine_id', $machine->id)->delete();

            $batch = [];
            foreach ($normalized as $software) {
                $installDate = $software['install_date'] ?? $software['installDate'] ?? null;
                if (is_string($installDate) && strlen($installDate) > 10) {
                    $installDate = substr($installDate, 0, 10);
                }

                $batch[] = [
                    'company_id'    => $machine->company_id,
                    'machine_id'    => $machine->id,
                    'software_name' => $software['name'],
                    'version'       => $software['version'],
                    'publisher'     => $software['publisher'],
                    'install_date'  => $installDate,
                    'architecture'  => $software['architecture'],
                    'collected_at'  => now(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }

            if (!empty($batch)) {
                SoftwareInventory::insert($batch);
            }

            Log::info('SoftwareInventoryProcessor: Processed software inventory', [
                'machine_id' => $machine->id,
                'count'      => count($batch),
            ]);
        } catch (\Throwable $e) {
            Log::error('SoftwareInventoryProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}