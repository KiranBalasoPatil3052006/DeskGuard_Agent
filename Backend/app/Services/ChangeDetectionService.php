<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChangeHistory;
use App\Models\Machine;
use Illuminate\Support\Facades\Log;

class ChangeDetectionService
{
    private BaselineService $baselineService;

    public function __construct(BaselineService $baselineService)
    {
        $this->baselineService = $baselineService;
    }

    public function detectHardwareChanges(Machine $machine, array $currentComponents): void
    {
        $baseline = $this->baselineService->getHardwareBaseline($machine);
        $baselineKeys = array_keys($baseline);

        $currentMap = [];
        foreach ($currentComponents as $comp) {
            $compName = $comp['component'] ?? $comp['name'] ?? 'Unknown';
            $serial = $comp['serial_number'] ?? $comp['serialNumber'] ?? $comp['serialNumber'] ?? $compName;
            $key = $compName . '|' . $serial;
            $currentMap[$key] = $comp;
        }

        $currentKeys = array_keys($currentMap);

        $added = array_diff($currentKeys, $baselineKeys);
        $removed = array_diff($baselineKeys, $currentKeys);

        foreach ($added as $key) {
            $comp = $currentMap[$key];
            $compName = $comp['component'] ?? $comp['name'] ?? 'Unknown';
            ChangeHistory::create([
                'company_id' => $machine->company_id,
                'machine_id' => $machine->id,
                'category' => 'hardware',
                'change_type' => 'added',
                'item_identifier' => $comp['serial_number'] ?? $comp['serialNumber'] ?? $key,
                'item_label' => $compName . ' ' . ($comp['model'] ?? ''),
                'description' => "New hardware detected: {$compName}",
                'metadata' => $comp,
                'detected_at' => now(),
            ]);
        }

        foreach ($removed as $key) {
            $bl = $baseline[$key];
            $blArr = is_array($bl) ? $bl : $bl->toArray();
            $compName = $blArr['component'] ?? 'Unknown';
            ChangeHistory::create([
                'company_id' => $machine->company_id,
                'machine_id' => $machine->id,
                'category' => 'hardware',
                'change_type' => 'removed',
                'item_identifier' => $blArr['serial_number'] ?? $key,
                'item_label' => $compName . ' ' . ($blArr['model'] ?? ''),
                'description' => "Hardware removed: {$compName}",
                'metadata' => $blArr,
                'detected_at' => now(),
            ]);
        }

        if (!empty($added) || !empty($removed)) {
            Log::info('ChangeDetectionService: Hardware changes detected', [
                'machine_id' => $machine->id,
                'added' => count($added),
                'removed' => count($removed),
            ]);
        }
    }

    public function detectSoftwareChanges(Machine $machine, array $currentSoftware): void
    {
        $baseline = $this->baselineService->getSoftwareBaseline($machine);
        $baselineNames = array_keys($baseline);

        $currentNames = [];
        $currentMap = [];
        foreach ($currentSoftware as $sw) {
            $name = $sw['name'] ?? $sw['displayName'] ?? $sw['software_name'] ?? 'Unknown';
            $currentNames[] = $name;
            $currentMap[$name] = $sw;
        }

        $added = array_diff($currentNames, $baselineNames);
        $removed = array_diff($baselineNames, $currentNames);

        foreach ($added as $name) {
            $sw = $currentMap[$name];
            $version = $sw['version'] ?? 'N/A';
            ChangeHistory::create([
                'company_id' => $machine->company_id,
                'machine_id' => $machine->id,
                'category' => 'software',
                'change_type' => 'added',
                'item_identifier' => $name,
                'item_label' => $name,
                'description' => "Software installed: {$name} v{$version}",
                'metadata' => $sw,
                'detected_at' => now(),
            ]);
        }

        foreach ($removed as $name) {
            $bl = $baseline[$name];
            $blArr = is_array($bl) ? $bl : $bl->toArray();
            ChangeHistory::create([
                'company_id' => $machine->company_id,
                'machine_id' => $machine->id,
                'category' => 'software',
                'change_type' => 'removed',
                'item_identifier' => $name,
                'item_label' => $name,
                'description' => "Software uninstalled: {$name}",
                'metadata' => $blArr,
                'detected_at' => now(),
            ]);
        }

        if (!empty($added) || !empty($removed)) {
            Log::info('ChangeDetectionService: Software changes detected', [
                'machine_id' => $machine->id,
                'added' => count($added),
                'removed' => count($removed),
            ]);
        }
    }
}