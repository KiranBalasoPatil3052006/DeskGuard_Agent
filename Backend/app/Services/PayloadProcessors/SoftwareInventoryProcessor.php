<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\SoftwareInventory;
use Illuminate\Support\Facades\Log;

class SoftwareInventoryProcessor
{
    public function process(Machine $machine, array $payload): void
    {
        try {
            $softwareList = $payload['softwareInventory'] ?? $payload['software_inventory'] ?? [];
            if (empty($softwareList)) {
                return;
            }

            SoftwareInventory::where('machine_id', $machine->id)->delete();

            $batch = [];
            foreach ($softwareList as $software) {
                $name = $software['displayName'] ?? $software['display_name'] ?? $software['softwareName'] ?? null;
                if (empty($name)) {
                    continue;
                }

                $installDate = $software['installDate'] ?? $software['install_date'] ?? null;
                if (is_string($installDate) && strlen($installDate) > 10) {
                    $installDate = substr($installDate, 0, 10);
                }

                $batch[] = [
                    'company_id'    => $machine->company_id,
                    'machine_id'    => $machine->id,
                    'software_name' => $name,
                    'version'       => $software['displayVersion'] ?? $software['display_version'] ?? $software['version'] ?? null,
                    'publisher'     => $software['publisher'] ?? null,
                    'install_date'  => $installDate,
                    'architecture'  => $software['architecture'] ?? $software['systemArchitecture'] ?? null,
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
