<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\InventoryDataDTO;
use App\DTOs\SecurityDataDTO;
use App\Enums\EventType;
use App\Exceptions\InventorySyncException;
use App\Exceptions\MachineNotFoundException;
use App\Models\AntivirusStatus;
use App\Models\FirewallStatus;
use App\Models\HardwareInventory;
use App\Models\LoginActivity;
use App\Models\Machine;
use App\Models\SoftwareInventory;
use App\Models\UsbActivity;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class InventoryService
 *
 * Processes hardware, software, and security inventory data received
 * from machine agents. All methods resolve the machine by its UID first.
 *
 * @package App\Services
 */
class InventoryService
{
    /**
     * The audit log service for recording inventory events.
     *
     * @var AuditLogService
     */
    private AuditLogService $auditLogService;

    /**
     * InventoryService constructor.
     *
     * @param AuditLogService $auditLogService
     */
    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Process and store hardware inventory data for a machine.
     *
     * @param  InventoryDataDTO  $dto
     * @return void
     *
     * @throws MachineNotFoundException
     * @throws InventorySyncException
     */
    public function processHardwareInventory(InventoryDataDTO $dto): void
    {
        try {
            $data = $dto->toArray();
            $machine = $this->findMachineByUid($data['machine_uid'] ?? '');

            $hardwareItems = $data['hardware'] ?? [];

            if (empty($hardwareItems)) {
                Log::info('No hardware inventory data to process', [
                    'machine_uid' => $data['machine_uid'] ?? '',
                ]);
                return;
            }

            foreach ($hardwareItems as $item) {
                HardwareInventory::create([
                    'company_id'    => $machine->company_id,
                    'machine_id'    => $machine->id,
                    'hardware_type' => $item['hardware_type'] ?? null,
                    'manufacturer'  => $item['manufacturer'] ?? null,
                    'model'         => $item['model'] ?? null,
                    'serial_number' => $item['serial_number'] ?? null,
                    'revision'      => $item['revision'] ?? null,
                    'collected_at'  => now(),
                ]);
            }

            $this->auditLogService->log(
                EventType::Sync->value,
                'Hardware inventory synced for machine: ' . $machine->machine_uid . ' (' . count($hardwareItems) . ' items)',
                null,
                ['count' => count($hardwareItems)],
                null,
                $machine
            );

            Log::info('Hardware inventory processed', [
                'machine_id'  => $machine->id,
                'machine_uid' => $machine->machine_uid,
                'items_count' => count($hardwareItems),
            ]);
        } catch (MachineNotFoundException | InventorySyncException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('InventoryService::processHardwareInventory - Failed to process hardware inventory', [
                'machine_uid' => $data['machine_uid'] ?? 'unknown',
                'error'       => $e->getMessage(),
            ]);
            throw new InventorySyncException(
                'Failed to process hardware inventory.',
                500,
                ['machine_uid' => $data['machine_uid'] ?? 'unknown']
            );
        }
    }

    /**
     * Process and store software inventory data for a machine.
     *
     * @param  InventoryDataDTO  $dto
     * @return void
     *
     * @throws MachineNotFoundException
     * @throws InventorySyncException
     */
    public function processSoftwareInventory(InventoryDataDTO $dto): void
    {
        try {
            $data = $dto->toArray();
            $machine = $this->findMachineByUid($data['machine_uid'] ?? '');

            $softwareItems = $data['software'] ?? [];

            if (empty($softwareItems)) {
                Log::info('No software inventory data to process', [
                    'machine_uid' => $data['machine_uid'] ?? '',
                ]);
                return;
            }

            foreach ($softwareItems as $item) {
                SoftwareInventory::create([
                    'company_id'    => $machine->company_id,
                    'machine_id'    => $machine->id,
                    'software_name' => $item['software_name'] ?? null,
                    'version'       => $item['version'] ?? null,
                    'publisher'     => $item['publisher'] ?? null,
                    'install_date'  => $item['install_date'] ?? null,
                    'collected_at'  => now(),
                ]);
            }

            $this->auditLogService->log(
                EventType::Sync->value,
                'Software inventory synced for machine: ' . $machine->machine_uid . ' (' . count($softwareItems) . ' items)',
                null,
                ['count' => count($softwareItems)],
                null,
                $machine
            );

            Log::info('Software inventory processed', [
                'machine_id'  => $machine->id,
                'machine_uid' => $machine->machine_uid,
                'items_count' => count($softwareItems),
            ]);
        } catch (MachineNotFoundException | InventorySyncException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('InventoryService::processSoftwareInventory - Failed to process software inventory', [
                'machine_uid' => $data['machine_uid'] ?? 'unknown',
                'error'       => $e->getMessage(),
            ]);
            throw new InventorySyncException(
                'Failed to process software inventory.',
                500,
                ['machine_uid' => $data['machine_uid'] ?? 'unknown']
            );
        }
    }

    /**
     * Process and store security-related data for a machine.
     *
     * Handles antivirus status, firewall configuration, login activities,
     * and USB device events.
     *
     * @param  SecurityDataDTO  $dto
     * @return void
     *
     * @throws MachineNotFoundException
     * @throws InventorySyncException
     */
    public function processSecurityData(SecurityDataDTO $dto): void
    {
        try {
            $data = $dto->toArray();
            $machine = $this->findMachineByUid($data['machine_uid'] ?? '');

            DB::transaction(function () use ($machine, $data) {
                $this->processAntivirusData($machine, $data['antivirus'] ?? []);
                $this->processFirewallData($machine, $data['firewall'] ?? []);
                $this->processLoginActivities($machine, $data['login_activities'] ?? []);
                $this->processUsbActivities($machine, $data['usb_activities'] ?? []);
            });

            $this->auditLogService->log(
                EventType::Sync->value,
                'Security data synced for machine: ' . $machine->machine_uid,
                null,
                null,
                null,
                $machine
            );

            Log::info('Security data processed', [
                'machine_id'  => $machine->id,
                'machine_uid' => $machine->machine_uid,
            ]);
        } catch (MachineNotFoundException | InventorySyncException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('InventoryService::processSecurityData - Failed to process security data', [
                'machine_uid' => $data['machine_uid'] ?? 'unknown',
                'error'       => $e->getMessage(),
            ]);
            throw new InventorySyncException(
                'Failed to process security data.',
                500,
                ['machine_uid' => $data['machine_uid'] ?? 'unknown']
            );
        }
    }

    /**
     * Find a machine by its UID or throw an exception.
     *
     * @param  string  $machineUid
     * @return Machine
     *
     * @throws MachineNotFoundException
     */
    private function findMachineByUid(string $machineUid): Machine
    {
        $machine = Machine::where('machine_uid', $machineUid)->first();

        if (!$machine) {
            throw new MachineNotFoundException(
                'Machine not found with UID: ' . $machineUid,
                404,
                ['machine_uid' => $machineUid]
            );
        }

        return $machine;
    }

    /**
     * Process antivirus status data.
     *
     * @param  Machine  $machine
     * @param  array    $antivirusData
     * @return void
     */
    private function processAntivirusData(Machine $machine, array $antivirusData): void
    {
        if (empty($antivirusData)) {
            return;
        }

        AntivirusStatus::create([
            'company_id'           => $machine->company_id,
            'machine_id'           => $machine->id,
            'product_name'         => $antivirusData['product_name'] ?? null,
            'is_enabled'           => $antivirusData['is_enabled'] ?? null,
            'is_updated'           => $antivirusData['is_updated'] ?? null,
            'real_time_protection' => $antivirusData['real_time_protection'] ?? null,
            'definition_version'   => $antivirusData['definition_version'] ?? null,
            'engine_version'       => $antivirusData['engine_version'] ?? null,
            'collected_at'         => now(),
        ]);
    }

    /**
     * Process firewall status data.
     *
     * @param  Machine  $machine
     * @param  array    $firewallData
     * @return void
     */
    private function processFirewallData(Machine $machine, array $firewallData): void
    {
        if (empty($firewallData)) {
            return;
        }

        FirewallStatus::create([
            'company_id'   => $machine->company_id,
            'machine_id'   => $machine->id,
            'display_name' => $firewallData['display_name'] ?? null,
            'is_enabled'   => $firewallData['is_enabled'] ?? null,
            'profile'      => $firewallData['profile'] ?? null,
            'collected_at' => now(),
        ]);
    }

    /**
     * Process login activity records.
     *
     * @param  Machine  $machine
     * @param  array    $loginActivities
     * @return void
     */
    private function processLoginActivities(Machine $machine, array $loginActivities): void
    {
        foreach ($loginActivities as $activity) {
            LoginActivity::create([
                'company_id'   => $machine->company_id,
                'machine_id'   => $machine->id,
                'username'     => $activity['username'] ?? null,
                'session_type' => $activity['session_type'] ?? null,
                'logon_id'     => $activity['logon_id'] ?? null,
                'logon_time'   => $activity['logon_time'] ?? null,
                'logoff_time'  => $activity['logoff_time'] ?? null,
                'collected_at' => now(),
            ]);
        }
    }

    /**
     * Process USB activity events.
     *
     * @param  Machine  $machine
     * @param  array    $usbActivities
     * @return void
     */
    private function processUsbActivities(Machine $machine, array $usbActivities): void
    {
        foreach ($usbActivities as $activity) {
            UsbActivity::create([
                'company_id'    => $machine->company_id,
                'machine_id'    => $machine->id,
                'device_name'   => $activity['device_name'] ?? null,
                'device_serial' => $activity['device_serial'] ?? null,
                'vendor_id'     => $activity['vendor_id'] ?? null,
                'product_id'    => $activity['product_id'] ?? null,
                'drive_letter'  => $activity['drive_letter'] ?? null,
                'action'        => $activity['action'] ?? null,
                'collected_at'  => now(),
            ]);
        }
    }
}
