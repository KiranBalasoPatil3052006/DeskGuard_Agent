<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\HardwareInventory;
use Illuminate\Support\Facades\Log;

class HardwareInventoryProcessor
{
    public function process(Machine $machine, array $payload): void
    {
        try {
            $inventory = $payload['hardwareInventory'] ?? $payload['hardware_inventory'] ?? [];
            if (empty($inventory)) {
                return;
            }

            $totalMemoryBytes = $inventory['totalMemoryBytes'] ?? $inventory['total_memory_bytes'] ?? null;
            $ramGb = $totalMemoryBytes !== null ? round($totalMemoryBytes / 1073741824, 2) : null;

            HardwareInventory::updateOrCreate(
                ['machine_id' => $machine->id],
                [
                    'manufacturer'         => $inventory['manufacturer'] ?? null,
                    'model'                => $inventory['model'] ?? null,
                    'serial_number'        => $inventory['serialNumber'] ?? $inventory['serial_number'] ?? null,
                    'bios_version'         => $inventory['biosVersion'] ?? $inventory['bios_version'] ?? null,
                    'bios_vendor'          => $inventory['biosVendor'] ?? $inventory['bios_vendor'] ?? null,
                    'bios_release_date'    => $inventory['biosReleaseDate'] ?? $inventory['bios_release_date'] ?? null,
                    'processor_name'       => $inventory['processorName'] ?? $inventory['processor_name'] ?? null,
                    'processor_cores'      => $inventory['processorCores'] ?? $inventory['processor_cores'] ?? null,
                    'processor_threads'    => $inventory['processorLogicalThreads'] ?? $inventory['processorThreads'] ?? $inventory['processor_threads'] ?? null,
                    'processor_clock_speed' => $inventory['processorClockSpeed'] ?? $inventory['processor_clock_speed'] ?? null,
                    'system_architecture'  => $inventory['systemArchitecture'] ?? $inventory['system_architecture'] ?? null,
                    'ram_total_gb'         => $ramGb,
                    'ram_type'             => $inventory['ramType'] ?? $inventory['ram_type'] ?? null,
                    'disk_model'           => $inventory['diskModel'] ?? $inventory['disk_model'] ?? null,
                    'disk_type'            => $inventory['diskType'] ?? $inventory['disk_type'] ?? null,
                    'disk_size_gb'         => $inventory['diskSizeGb'] ?? $inventory['disk_size_gb'] ?? null,
                    'gpu_name'             => $inventory['gpuName'] ?? $inventory['gpu_name'] ?? null,
                    'collected_at'         => now(),
                ]
            );

            Machine::where('id', $machine->id)->update([
                'manufacturer'  => $inventory['manufacturer'] ?? null,
                'model'         => $inventory['model'] ?? null,
                'serial_number' => $inventory['serialNumber'] ?? $inventory['serial_number'] ?? null,
                'bios_version'  => $inventory['biosVersion'] ?? $inventory['bios_version'] ?? null,
                'processor'     => $inventory['processorName'] ?? $inventory['processor_name'] ?? null,
                'ram_gb'        => $ramGb,
                'operating_system' => $inventory['operatingSystem'] ?? $inventory['osName'] ?? null,
                'os_version'    => $inventory['osVersion'] ?? $inventory['os_version'] ?? null,
            ]);

            Log::info('HardwareInventoryProcessor: Processed hardware inventory', [
                'machine_id' => $machine->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('HardwareInventoryProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
