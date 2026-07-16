<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * InventoryDataDTO
 *
 * Data Transfer Object for machine inventory snapshots.
 * Carries a list of hardware components and installed software
 * collected from the agent during periodic inventory sweeps.
 */
class InventoryDataDTO extends BaseDTO
{
    /** Unique identifier of the source machine */
    protected string $machine_uid;

    /** List of hardware components and their details */
    protected array $hardware = [];

    /** List of installed software packages */
    protected array $software = [];

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
     * Set the hardware inventory array.
     *
     * @param array $value
     */
    public function setHardware(array $value): void
    {
        $this->hardware = $value;
        $this->properties['hardware'] = $value;
    }

    /**
     * Set the software inventory array.
     *
     * @param array $value
     */
    public function setSoftware(array $value): void
    {
        $this->software = $value;
        $this->properties['software'] = $value;
    }
}
