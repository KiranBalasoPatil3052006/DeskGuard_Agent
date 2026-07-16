<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * SecurityDataDTO
 *
 * Data Transfer Object for machine security posture data.
 * Contains antivirus status, firewall configuration, login
 * activity records, and USB device events.
 */
class SecurityDataDTO extends BaseDTO
{
    /** Unique identifier of the source machine */
    protected string $machine_uid;

    /** Antivirus product details and status */
    protected array $antivirus = [];

    /** Firewall configuration and state */
    protected array $firewall = [];

    /** Recent login activity records */
    protected array $login_activities = [];

    /** Recent USB device connection events */
    protected array $usb_activities = [];

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
     * Set the antivirus data array.
     *
     * @param array $value
     */
    public function setAntivirus(array $value): void
    {
        $this->antivirus = $value;
        $this->properties['antivirus'] = $value;
    }

    /**
     * Set the firewall data array.
     *
     * @param array $value
     */
    public function setFirewall(array $value): void
    {
        $this->firewall = $value;
        $this->properties['firewall'] = $value;
    }

    /**
     * Set the login activities array.
     *
     * @param array $value
     */
    public function setLoginActivities(array $value): void
    {
        $this->login_activities = $value;
        $this->properties['login_activities'] = $value;
    }

    /**
     * Set the USB activities array.
     *
     * @param array $value
     */
    public function setUsbActivities(array $value): void
    {
        $this->usb_activities = $value;
        $this->properties['usb_activities'] = $value;
    }
}
