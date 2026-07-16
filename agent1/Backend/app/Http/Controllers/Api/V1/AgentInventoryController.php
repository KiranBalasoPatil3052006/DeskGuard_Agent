<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HardwareInventory;
use App\Models\Machine;
use App\Models\SoftwareInventory;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AgentInventoryController
 *
 * Receives inventory data from the C# agent sent to:
 *   POST /api/v1/inventory/hardware
 *   POST /api/v1/inventory/software
 *
 * The agent sends these as separate payloads on longer intervals
 * (default 24 hours).
 */
class AgentInventoryController extends Controller
{
    use ApiResponseTrait;

    public function hardware(Request $request): JsonResponse
    {
        try {
            $machine = $this->getMachine($request);
            if (!$machine) {
                return $this->errorResponse('Machine not found.', [], 404);
            }

            $data = $request->input('items', $request->all());

            $totalMemoryBytes = $data['totalMemoryBytes'] ?? null;
            $ramGb = $totalMemoryBytes !== null ? (int)round($totalMemoryBytes / 1073741824, 0) : null;

            HardwareInventory::updateOrCreate(
                ['machine_id' => $machine->id],
                [
                    'company_id'            => $machine->company_id,
                    'manufacturer'          => $data['manufacturer'] ?? null,
                    'model'                 => $data['model'] ?? null,
                    'serial_number'         => $data['serialNumber'] ?? $data['serial_number'] ?? null,
                    'bios_version'          => $data['biosVersion'] ?? $data['bios_version'] ?? null,
                    'bios_vendor'           => $data['biosVendor'] ?? $data['bios_vendor'] ?? null,
                    'bios_release_date'     => $data['biosReleaseDate'] ?? $data['bios_release_date'] ?? null,
                    'processor_name'        => $data['processorName'] ?? $data['processor_name'] ?? null,
                    'processor_cores'       => $data['processorCores'] ?? $data['processor_cores'] ?? null,
                    'processor_threads'     => $data['processorLogicalThreads'] ?? $data['processorThreads'] ?? $data['processor_threads'] ?? null,
                    'processor_clock_speed' => $data['processorClockSpeed'] ?? $data['processor_clock_speed'] ?? null,
                    'system_architecture'   => $data['systemArchitecture'] ?? $data['system_architecture'] ?? null,
                    'ram_total_gb'          => $ramGb,
                    'ram_type'              => $data['ramType'] ?? $data['ram_type'] ?? null,
                    'disk_model'            => $data['diskModel'] ?? $data['disk_model'] ?? null,
                    'disk_type'             => $data['diskType'] ?? $data['disk_type'] ?? null,
                    'disk_size_gb'          => $data['diskSizeGb'] ?? $data['disk_size_gb'] ?? null,
                    'gpu_name'              => $data['gpuName'] ?? $data['gpu_name'] ?? null,
                    'collected_at'          => now(),
                ]
            );

            Machine::where('id', $machine->id)->update([
                'manufacturer'     => $data['manufacturer'] ?? null,
                'model'            => $data['model'] ?? null,
                'serial_number'    => $data['serialNumber'] ?? $data['serial_number'] ?? null,
                'bios_version'     => $data['biosVersion'] ?? $data['bios_version'] ?? null,
                'processor'        => $data['processorName'] ?? $data['processor_name'] ?? null,
                'ram_gb'           => $ramGb,
                'operating_system' => $data['operatingSystem'] ?? $data['osName'] ?? null,
                'os_version'       => $data['osVersion'] ?? $data['os_version'] ?? null,
            ]);

            Log::info('AgentInventoryController: Hardware inventory processed', [
                'machine_id' => $machine->id,
            ]);

            return $this->successResponse(null, 'Hardware inventory processed.');
        } catch (Exception $e) {
            Log::error('AgentInventoryController::hardware - Failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to process hardware inventory.', [], 500);
        }
    }

    public function software(Request $request): JsonResponse
    {
        try {
            $machine = $this->getMachine($request);
            if (!$machine) {
                return $this->errorResponse('Machine not found.', [], 404);
            }

            $items = $request->input('items', []);
            if (empty($items)) {
                $all = $request->all();
                if (is_array($all) && isset($all[0])) {
                    $items = $all;
                } else {
                    return $this->successResponse(null, 'No software inventory to process.');
                }
            }

            SoftwareInventory::where('machine_id', $machine->id)->delete();

            $batch = [];
            foreach ($items as $item) {
                $name = $item['displayName'] ?? $item['softwareName'] ?? null;
                if (empty($name)) {
                    continue;
                }

                $installDate = $item['installDate'] ?? null;
                if (is_string($installDate) && strlen($installDate) > 10) {
                    $installDate = substr($installDate, 0, 10);
                }

                $is64Bit = $item['is64Bit'] ?? $item['is_64_bit'] ?? null;
                $architecture = $is64Bit !== null ? ($is64Bit ? '64-bit' : '32-bit') : null;

                $regKey = $item['registryKeyPath'] ?? $item['registry_key_path'] ?? null;
                $estSize = $item['estimatedSizeMb'] ?? $item['estimated_size_mb'] ?? null;

                $batch[] = [
                    'company_id'       => $machine->company_id,
                    'machine_id'       => $machine->id,
                    'software_name'    => $name,
                    'version'          => $item['displayVersion'] ?? $item['version'] ?? null,
                    'publisher'        => $item['publisher'] ?? null,
                    'install_date'     => $installDate,
                    'architecture'     => $architecture,
                    'registry_key_path' => $regKey,
                    'estimated_size_mb' => $estSize,
                    'collected_at'     => now(),
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }

            if (!empty($batch)) {
                SoftwareInventory::insert($batch);
            }

            Log::info('AgentInventoryController: Software inventory processed', [
                'machine_id' => $machine->id,
                'count'      => count($batch),
            ]);

            return $this->successResponse(null, 'Software inventory processed.');
        } catch (Exception $e) {
            Log::error('AgentInventoryController::software - Failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to process software inventory.', [], 500);
        }
    }

    private function getMachine(Request $request): ?Machine
    {
        $machine = $request->attributes->get('machine');

        if ($machine) {
            return $machine;
        }

        $machineUid = $request->input('machineId') ?? $request->input('machine_uid') ?? '';
        if (!empty($machineUid)) {
            return Machine::firstOrCreate(
                ['machine_uid' => $machineUid],
                [
                    'is_online'  => true,
                    'is_active'  => true,
                    'last_heartbeat_at' => now(),
                ]
            );
        }

        return null;
    }
}
