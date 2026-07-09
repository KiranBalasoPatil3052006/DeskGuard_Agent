<?php

declare(strict_types=1);

namespace App\DTOs;

class TelemetryPayloadDTO extends BaseDTO
{
    protected string $machineId;
    protected ?string $computerName = null;
    protected ?string $collectedAt = null;

    protected ?array $systemInfo = null;
    protected ?array $cpu = null;
    protected ?array $memory = null;
    protected ?array $disks = null;
    protected ?array $battery = null;
    protected ?array $networkAdapters = null;
    protected ?array $hardwareInventory = null;
    protected ?array $softwareInventory = null;
    protected ?array $processes = null;
    protected ?array $services = null;
    protected ?array $antivirus = null;
    protected ?array $firewall = null;
    protected ?array $windowsUpdates = null;
    protected ?array $eventLogs = null;
    protected ?array $loginActivities = null;
    protected ?array $startupPrograms = null;
    protected ?array $connectedDevices = null;
    protected ?array $deviceEvents = null;

    public function setMachineId(string $value): void
    {
        $this->machineId = $value;
        $this->properties['machineId'] = $value;
    }

    public function setComputerName(?string $value): void
    {
        $this->computerName = $value;
        $this->properties['computerName'] = $value;
    }

    public function setCollectedAt(?string $value): void
    {
        $this->collectedAt = $value;
        $this->properties['collectedAt'] = $value;
    }

    public function setSystemInfo(?array $value): void
    {
        $this->systemInfo = $value;
        $this->properties['systemInfo'] = $value;
    }

    public function setCpu(?array $value): void
    {
        $this->cpu = $value;
        $this->properties['cpu'] = $value;
    }

    public function setMemory(?array $value): void
    {
        $this->memory = $value;
        $this->properties['memory'] = $value;
    }

    public function setDisks(?array $value): void
    {
        $this->disks = $value;
        $this->properties['disks'] = $value;
    }

    public function setBattery(?array $value): void
    {
        $this->battery = $value;
        $this->properties['battery'] = $value;
    }

    public function setNetworkAdapters(?array $value): void
    {
        $this->networkAdapters = $value;
        $this->properties['networkAdapters'] = $value;
    }

    public function setHardwareInventory(?array $value): void
    {
        $this->hardwareInventory = $value;
        $this->properties['hardwareInventory'] = $value;
    }

    public function setSoftwareInventory(?array $value): void
    {
        $this->softwareInventory = $value;
        $this->properties['softwareInventory'] = $value;
    }

    public function setProcesses(?array $value): void
    {
        $this->processes = $value;
        $this->properties['processes'] = $value;
    }

    public function setServices(?array $value): void
    {
        $this->services = $value;
        $this->properties['services'] = $value;
    }

    public function setAntivirus(?array $value): void
    {
        $this->antivirus = $value;
        $this->properties['antivirus'] = $value;
    }

    public function setFirewall(?array $value): void
    {
        $this->firewall = $value;
        $this->properties['firewall'] = $value;
    }

    public function setWindowsUpdates(?array $value): void
    {
        $this->windowsUpdates = $value;
        $this->properties['windowsUpdates'] = $value;
    }

    public function setEventLogs(?array $value): void
    {
        $this->eventLogs = $value;
        $this->properties['eventLogs'] = $value;
    }

    public function setLoginActivities(?array $value): void
    {
        $this->loginActivities = $value;
        $this->properties['loginActivities'] = $value;
    }

    public function setStartupPrograms(?array $value): void
    {
        $this->startupPrograms = $value;
        $this->properties['startupPrograms'] = $value;
    }

    public function setConnectedDevices(?array $value): void
    {
        $this->connectedDevices = $value;
        $this->properties['connectedDevices'] = $value;
    }

    public function setDeviceEvents(?array $value): void
    {
        $this->deviceEvents = $value;
        $this->properties['deviceEvents'] = $value;
    }
}
