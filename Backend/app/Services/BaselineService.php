<?php

/**
 * BaselineService
 *
 * Manages the approved baseline state for machines across all categories:
 * hardware, software, security, and configuration.
 *
 * Baselines represent the "known good state" of a machine and are used
 * by the change detection system to identify deviations. When a change
 * is approved by an admin, the baseline is updated to reflect the new state.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\ConfigurationBaseline;
use App\Models\HardwareBaseline;
use App\Models\Machine;
use App\Models\SecurityBaseline;
use App\Models\SoftwareBaseline;
use Illuminate\Support\Facades\Log;

class BaselineService
{
    /**
     * Sync hardware baseline components for a machine.
     * Uses updateOrCreate to upsert each component by (machine_id, component, serial_number).
     *
     * @param Machine $machine The machine to update baselines for.
     * @param array $components Array of hardware component data.
     * @return void
     */
    public function syncHardwareBaseline(Machine $machine, array $components): void
    {
        foreach ($components as $component) {
            $compName = $component['component'] ?? $component['name'] ?? 'Unknown';
            $serial = $component['serial_number'] ?? $component['serial'] ?? $component['serialNumber'] ?? $compName;

            HardwareBaseline::updateOrCreate(
                [
                    'machine_id' => $machine->id,
                    'component' => $compName,
                    'serial_number' => $serial,
                ],
                [
                    'company_id' => $machine->company_id,
                    'manufacturer' => $component['manufacturer'] ?? null,
                    'model' => $component['model'] ?? null,
                    'capacity' => $component['capacity'] ?? null,
                    'speed' => $component['speed'] ?? null,
                    'slot_info' => $component['slot_info'] ?? $component['slotInfo'] ?? null,
                    'properties' => $component['properties'] ?? null,
                    'baselined_at' => now(),
                ]
            );
        }

        Log::info('BaselineService: Hardware baseline synced', [
            'machine_id' => $machine->id,
            'count' => count($components),
        ]);
    }

    /**
     * Sync software baseline entries for a machine.
     * Uses updateOrCreate to upsert each software by (machine_id, software_name).
     *
     * @param Machine $machine The machine to update baselines for.
     * @param array $softwareList Array of software inventory data.
     * @return void
     */
    public function syncSoftwareBaseline(Machine $machine, array $softwareList): void
    {
        foreach ($softwareList as $software) {
            $name = $software['name'] ?? $software['displayName'] ?? $software['software_name'] ?? 'Unknown';

            SoftwareBaseline::updateOrCreate(
                [
                    'machine_id' => $machine->id,
                    'software_name' => $name,
                ],
                [
                    'company_id' => $machine->company_id,
                    'version' => $software['version'] ?? null,
                    'publisher' => $software['publisher'] ?? null,
                    'architecture' => $software['architecture'] ?? null,
                    'baselined_at' => now(),
                ]
            );
        }

        Log::info('BaselineService: Software baseline synced', [
            'machine_id' => $machine->id,
            'count' => count($softwareList),
        ]);
    }

    /**
     * Sync security baseline entries for a machine.
     * Stores security posture indicators like antivirus status, firewall state, etc.
     *
     * @param Machine $machine The machine to update baselines for.
     * @param array $securityItems Array of security component data.
     * @return void
     */
    public function syncSecurityBaseline(Machine $machine, array $securityItems): void
    {
        foreach ($securityItems as $item) {
            $component = $item['component'] ?? $item['name'] ?? 'Unknown';

            SecurityBaseline::updateOrCreate(
                [
                    'machine_id' => $machine->id,
                    'component' => $component,
                ],
                [
                    'company_id' => $machine->company_id,
                    'value' => $item['value'] ?? null,
                    'baselined_at' => now(),
                ]
            );
        }

        Log::info('BaselineService: Security baseline synced', [
            'machine_id' => $machine->id,
            'count' => count($securityItems),
        ]);
    }

    /**
     * Sync configuration baseline entries for a machine.
     * Stores configuration settings like startup programs, service states, etc.
     *
     * @param Machine $machine The machine to update baselines for.
     * @param array $configItems Array of configuration setting data.
     * @return void
     */
    public function syncConfigurationBaseline(Machine $machine, array $configItems): void
    {
        foreach ($configItems as $item) {
            $key = $item['setting_key'] ?? $item['key'] ?? $item['name'] ?? 'Unknown';

            ConfigurationBaseline::updateOrCreate(
                [
                    'machine_id' => $machine->id,
                    'setting_key' => $key,
                ],
                [
                    'company_id' => $machine->company_id,
                    'setting_value' => $item['setting_value'] ?? $item['value'] ?? null,
                    'baselined_at' => now(),
                ]
            );
        }

        Log::info('BaselineService: Configuration baseline synced', [
            'machine_id' => $machine->id,
            'count' => count($configItems),
        ]);
    }

    /**
     * Get the hardware baseline for a machine, keyed by component|serial.
     *
     * @param Machine $machine The machine to retrieve baselines for.
     * @return array Key-value array of baseline entries.
     */
    public function getHardwareBaseline(Machine $machine): array
    {
        return HardwareBaseline::where('machine_id', $machine->id)
            ->get()
            ->keyBy(fn($item) => $item->component . '|' . ($item->serial_number ?? $item->component))
            ->toArray();
    }

    /**
     * Get the software baseline for a machine, keyed by software_name.
     *
     * @param Machine $machine The machine to retrieve baselines for.
     * @return array Key-value array of baseline entries.
     */
    public function getSoftwareBaseline(Machine $machine): array
    {
        return SoftwareBaseline::where('machine_id', $machine->id)
            ->get()
            ->keyBy('software_name')
            ->toArray();
    }

    /**
     * Get the security baseline for a machine, keyed by component.
     *
     * @param Machine $machine The machine to retrieve baselines for.
     * @return array Key-value array of baseline entries.
     */
    public function getSecurityBaseline(Machine $machine): array
    {
        return SecurityBaseline::where('machine_id', $machine->id)
            ->get()
            ->keyBy('component')
            ->toArray();
    }

    /**
     * Get the configuration baseline for a machine, keyed by setting_key.
     *
     * @param Machine $machine The machine to retrieve baselines for.
     * @return array Key-value array of baseline entries.
     */
    public function getConfigurationBaseline(Machine $machine): array
    {
        return ConfigurationBaseline::where('machine_id', $machine->id)
            ->get()
            ->keyBy('setting_key')
            ->toArray();
    }
}
