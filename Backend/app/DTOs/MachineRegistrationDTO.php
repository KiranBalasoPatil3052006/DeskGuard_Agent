<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * MachineRegistrationDTO
 *
 * Data Transfer Object used when a new machine agent registers
 * with the DeskGuard server. Carries the activation token and
 * basic identifying information about the machine.
 */
class MachineRegistrationDTO extends BaseDTO
{
    /** Unique identifier assigned to the machine */
    protected string $machine_uid;

    /** Activation token used to authorise registration */
    protected string $activation_token;

    /** Network hostname of the machine */
    protected ?string $hostname = null;

    /** Operating system identifier string */
    protected ?string $operating_system = null;

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
     * Set the activation token.
     *
     * @param string $value
     */
    public function setActivationToken(string $value): void
    {
        $this->activation_token = $value;
        $this->properties['activation_token'] = $value;
    }

    /**
     * Set the machine hostname.
     *
     * @param string|null $value
     */
    public function setHostname(?string $value): void
    {
        $this->hostname = $value;
        $this->properties['hostname'] = $value;
    }

    /**
     * Set the operating system string.
     *
     * @param string|null $value
     */
    public function setOperatingSystem(?string $value): void
    {
        $this->operating_system = $value;
        $this->properties['operating_system'] = $value;
    }
}
