<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * HealthDataDTO
 *
 * Data Transfer Object for machine health metrics.
 * Encapsulates CPU, RAM, disk, battery, and network readings
 * collected from the agent at regular intervals.
 */
class HealthDataDTO extends BaseDTO
{
    /** Unique identifier of the source machine */
    protected string $machine_uid;

    /** Current CPU usage percentage */
    protected ?float $cpu_percentage = null;

    /** Current CPU temperature in Celsius */
    protected ?float $cpu_temperature = null;

    /** current CPU clock speed in MHz */
    protected ?float $cpu_clock_speed = null;

    /** Number of logical CPU cores */
    protected ?int $cpu_core_count = null;

    /** Total physical RAM in bytes */
    protected ?int $ram_total_bytes = null;

    /** Currently used RAM in bytes */
    protected ?int $ram_used_bytes = null;

    /** Currently available RAM in bytes */
    protected ?int $ram_available_bytes = null;

    /** Total disk capacity in bytes */
    protected ?int $disk_total_bytes = null;

    /** Used disk space in bytes */
    protected ?int $disk_used_bytes = null;

    /** Free disk space in bytes */
    protected ?int $disk_free_bytes = null;

    /** Current battery charge percentage */
    protected ?float $battery_percentage = null;

    /** Whether the battery is currently charging */
    protected ?bool $battery_charging_status = null;

    /** Cumulative bytes received over the network */
    protected ?int $network_received_bytes = null;

    /** Cumulative bytes sent over the network */
    protected ?int $network_sent_bytes = null;

    /** Whether the machine is currently reachable */
    protected bool $online_status;

    /**
     * Set the machine UID.
     *
     * @param string $value
     */
    public function setMachineUid(string $value): void
    {
        $this->machine_uid = $value;
        $this->properties['machine_uid'] = $value;
    }

    /**
     * Set the CPU usage percentage.
     *
     * @param float|null $value
     */
    public function setCpuPercentage(?float $value): void
    {
        $this->cpu_percentage = $value;
        $this->properties['cpu_percentage'] = $value;
    }

    /**
     * Set the CPU temperature.
     *
     * @param float|null $value
     */
    public function setCpuTemperature(?float $value): void
    {
        $this->cpu_temperature = $value;
        $this->properties['cpu_temperature'] = $value;
    }

    /**
     * Set the CPU clock speed.
     *
     * @param float|null $value
     */
    public function setCpuClockSpeed(?float $value): void
    {
        $this->cpu_clock_speed = $value;
        $this->properties['cpu_clock_speed'] = $value;
    }

    /**
     * Set the CPU core count.
     *
     * @param int|null $value
     */
    public function setCpuCoreCount(?int $value): void
    {
        $this->cpu_core_count = $value;
        $this->properties['cpu_core_count'] = $value;
    }

    /**
     * Set total RAM in bytes.
     *
     * @param int|null $value
     */
    public function setRamTotalBytes(?int $value): void
    {
        $this->ram_total_bytes = $value;
        $this->properties['ram_total_bytes'] = $value;
    }

    /**
     * Set used RAM in bytes.
     *
     * @param int|null $value
     */
    public function setRamUsedBytes(?int $value): void
    {
        $this->ram_used_bytes = $value;
        $this->properties['ram_used_bytes'] = $value;
    }

    /**
     * Set available RAM in bytes.
     *
     * @param int|null $value
     */
    public function setRamAvailableBytes(?int $value): void
    {
        $this->ram_available_bytes = $value;
        $this->properties['ram_available_bytes'] = $value;
    }

    /**
     * Set total disk capacity in bytes.
     *
     * @param int|null $value
     */
    public function setDiskTotalBytes(?int $value): void
    {
        $this->disk_total_bytes = $value;
        $this->properties['disk_total_bytes'] = $value;
    }

    /**
     * Set used disk space in bytes.
     *
     * @param int|null $value
     */
    public function setDiskUsedBytes(?int $value): void
    {
        $this->disk_used_bytes = $value;
        $this->properties['disk_used_bytes'] = $value;
    }

    /**
     * Set free disk space in bytes.
     *
     * @param int|null $value
     */
    public function setDiskFreeBytes(?int $value): void
    {
        $this->disk_free_bytes = $value;
        $this->properties['disk_free_bytes'] = $value;
    }

    /**
     * Set the battery charge percentage.
     *
     * @param float|null $value
     */
    public function setBatteryPercentage(?float $value): void
    {
        $this->battery_percentage = $value;
        $this->properties['battery_percentage'] = $value;
    }

    /**
     * Set whether the battery is charging.
     *
     * @param bool|null $value
     */
    public function setBatteryChargingStatus(?bool $value): void
    {
        $this->battery_charging_status = $value;
        $this->properties['battery_charging_status'] = $value;
    }

    /**
     * Set cumulative network bytes received.
     *
     * @param int|null $value
     */
    public function setNetworkReceivedBytes(?int $value): void
    {
        $this->network_received_bytes = $value;
        $this->properties['network_received_bytes'] = $value;
    }

    /**
     * Set cumulative network bytes sent.
     *
     * @param int|null $value
     */
    public function setNetworkSentBytes(?int $value): void
    {
        $this->network_sent_bytes = $value;
        $this->properties['network_sent_bytes'] = $value;
    }

    /**
     * Set the online status of the machine.
     *
     * @param bool $value
     */
    public function setOnlineStatus(bool $value): void
    {
        $this->online_status = $value;
        $this->properties['online_status'] = $value;
    }
}
