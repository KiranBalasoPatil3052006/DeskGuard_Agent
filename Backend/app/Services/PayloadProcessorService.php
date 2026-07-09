<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HealthLog;
use App\Models\Machine;
use App\Services\PayloadProcessors\AlertProcessor;
use App\Services\PayloadProcessors\AntivirusProcessor;
use App\Services\PayloadProcessors\BatteryProcessor;
use App\Services\PayloadProcessors\CpuProcessor;
use App\Services\PayloadProcessors\DeviceEventProcessor;
use App\Services\PayloadProcessors\DeviceProcessor;
use App\Services\PayloadProcessors\DiskProcessor;
use App\Services\PayloadProcessors\EventLogProcessor;
use App\Services\PayloadProcessors\FirewallProcessor;
use App\Services\PayloadProcessors\HardwareInventoryProcessor;
use App\Services\PayloadProcessors\LoginActivityProcessor;
use App\Services\PayloadProcessors\MachineProcessor;
use App\Services\PayloadProcessors\MemoryProcessor;
use App\Services\PayloadProcessors\NetworkProcessor;
use App\Services\PayloadProcessors\ProcessProcessor;
use App\Services\PayloadProcessors\ServiceProcessor;
use App\Services\PayloadProcessors\SoftwareInventoryProcessor;
use App\Services\PayloadProcessors\StartupProgramProcessor;
use App\Services\PayloadProcessors\UpdateProcessor;
use App\Services\PayloadProcessors\UsbActivityProcessor;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PayloadProcessorService
 *
 * Orchestrates processing of a single agent health payload.
 * Creates ONE shared HealthLog row per payload cycle and passes it
 * to each metric processor (CPU, Memory, Disk, Battery) so they update
 * the same row rather than creating duplicate entries.
 */
class PayloadProcessorService
{
    private MachineProcessor $machineProcessor;
    private CpuProcessor $cpuProcessor;
    private MemoryProcessor $memoryProcessor;
    private DiskProcessor $diskProcessor;
    private BatteryProcessor $batteryProcessor;
    private NetworkProcessor $networkProcessor;
    private HardwareInventoryProcessor $hardwareInventoryProcessor;
    private SoftwareInventoryProcessor $softwareInventoryProcessor;
    private ProcessProcessor $processProcessor;
    private ServiceProcessor $serviceProcessor;
    private AntivirusProcessor $antivirusProcessor;
    private FirewallProcessor $firewallProcessor;
    private UpdateProcessor $updateProcessor;
    private UsbActivityProcessor $usbActivityProcessor;
    private EventLogProcessor $eventLogProcessor;
    private LoginActivityProcessor $loginActivityProcessor;
    private StartupProgramProcessor $startupProgramProcessor;
    private DeviceProcessor $deviceProcessor;
    private DeviceEventProcessor $deviceEventProcessor;
    private AlertProcessor $alertProcessor;

    public function __construct(
        MachineProcessor $machineProcessor,
        CpuProcessor $cpuProcessor,
        MemoryProcessor $memoryProcessor,
        DiskProcessor $diskProcessor,
        BatteryProcessor $batteryProcessor,
        NetworkProcessor $networkProcessor,
        HardwareInventoryProcessor $hardwareInventoryProcessor,
        SoftwareInventoryProcessor $softwareInventoryProcessor,
        ProcessProcessor $processProcessor,
        ServiceProcessor $serviceProcessor,
        AntivirusProcessor $antivirusProcessor,
        FirewallProcessor $firewallProcessor,
        UpdateProcessor $updateProcessor,
        UsbActivityProcessor $usbActivityProcessor,
        EventLogProcessor $eventLogProcessor,
        LoginActivityProcessor $loginActivityProcessor,
        StartupProgramProcessor $startupProgramProcessor,
        DeviceProcessor $deviceProcessor,
        DeviceEventProcessor $deviceEventProcessor,
        AlertProcessor $alertProcessor
    ) {
        $this->machineProcessor = $machineProcessor;
        $this->cpuProcessor = $cpuProcessor;
        $this->memoryProcessor = $memoryProcessor;
        $this->diskProcessor = $diskProcessor;
        $this->batteryProcessor = $batteryProcessor;
        $this->networkProcessor = $networkProcessor;
        $this->hardwareInventoryProcessor = $hardwareInventoryProcessor;
        $this->softwareInventoryProcessor = $softwareInventoryProcessor;
        $this->processProcessor = $processProcessor;
        $this->serviceProcessor = $serviceProcessor;
        $this->antivirusProcessor = $antivirusProcessor;
        $this->firewallProcessor = $firewallProcessor;
        $this->updateProcessor = $updateProcessor;
        $this->usbActivityProcessor = $usbActivityProcessor;
        $this->eventLogProcessor = $eventLogProcessor;
        $this->loginActivityProcessor = $loginActivityProcessor;
        $this->startupProgramProcessor = $startupProgramProcessor;
        $this->deviceProcessor = $deviceProcessor;
        $this->deviceEventProcessor = $deviceEventProcessor;
        $this->alertProcessor = $alertProcessor;
    }

    /**
     * Process a full agent health payload.
     *
     * Creates a single shared HealthLog row first, then passes it to
     * each metric processor (CPU, Memory, Disk, Battery) so they update
     * the same row. Other processors receive (Machine, payload) as before.
     */
    public function process(Machine $machine, array $payload): void
    {
        DB::transaction(function () use ($machine, $payload) {
            // Create ONE shared HealthLog row for this payload cycle.
            // Metric processors (CPU, Memory, Disk, Battery) will update this row
            // instead of each creating their own, eliminating duplicate entries.
            $healthLog = HealthLog::create([
                'company_id'  => $machine->company_id,
                'machine_id'  => $machine->id,
                'collected_at' => now(),
            ]);

            foreach ($this->getProcessors() as $name => $processor) {
                try {
                    // Metric processors accept HealthLog as 3rd arg;
                    // other processors ignore it (PHP allows extra args).
                    $processor($machine, $payload, $healthLog);
                } catch (\Throwable $e) {
                    Log::error("PayloadProcessorService: {$name} failed", [
                        'machine_id' => $machine->id,
                        'error'      => $e->getMessage(),
                        'trace'      => $e->getTraceAsString(),
                    ]);
                }
            }
        });
    }

    private function getProcessors(): array
    {
        return [
            'MachineProcessor'              => [$this->machineProcessor, 'process'],
            'CpuProcessor'                  => [$this->cpuProcessor, 'process'],
            'MemoryProcessor'               => [$this->memoryProcessor, 'process'],
            'DiskProcessor'                 => [$this->diskProcessor, 'process'],
            'BatteryProcessor'              => [$this->batteryProcessor, 'process'],
            'NetworkProcessor'              => [$this->networkProcessor, 'process'],
            'HardwareInventoryProcessor'    => [$this->hardwareInventoryProcessor, 'process'],
            'SoftwareInventoryProcessor'    => [$this->softwareInventoryProcessor, 'process'],
            'ProcessProcessor'              => [$this->processProcessor, 'process'],
            'ServiceProcessor'              => [$this->serviceProcessor, 'process'],
            'AntivirusProcessor'            => [$this->antivirusProcessor, 'process'],
            'FirewallProcessor'             => [$this->firewallProcessor, 'process'],
            'UpdateProcessor'               => [$this->updateProcessor, 'process'],
            'UsbActivityProcessor'           => [$this->usbActivityProcessor, 'process'],
            'EventLogProcessor'             => [$this->eventLogProcessor, 'process'],
            'LoginActivityProcessor'        => [$this->loginActivityProcessor, 'process'],
            'StartupProgramProcessor'       => [$this->startupProgramProcessor, 'process'],
            'DeviceProcessor'               => [$this->deviceProcessor, 'process'],
            'DeviceEventProcessor'          => [$this->deviceEventProcessor, 'process'],
            'AlertProcessor'                => [$this->alertProcessor, 'process'],
        ];
    }
}
