<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class Machine
 *
 * @property int $id
 * @property int|null $company_id
 * @property int|null $user_id
 * @property string $machine_uid
 * @property string|null $hostname
 * @property string|null $device_name
 * @property string|null $operating_system
 * @property string|null $os_version
 * @property string|null $manufacturer
 * @property string|null $model
 * @property string|null $serial_number
 * @property string|null $bios_version
 * @property string|null $processor
 * @property float|null $ram_gb
 * @property bool $is_online
 * @property Carbon|null $last_heartbeat_at
 * @property string|null $activation_token
 * @property Carbon|null $activated_at
 * @property bool $is_active
 * @property string|null $status
 * @property string|null $api_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Company|null $company
 * @property-read User|null $assignedUser
 * @property-read MachineCurrentStatus|null $currentStatus
 * @property-read Collection<int, HealthLog> $healthLogs
 * @property-read Collection<int, HardwareInventory> $hardwareInventories
 * @property-read Collection<int, SoftwareInventory> $softwareInventories
 * @property-read Collection<int, AntivirusStatus> $antivirusStatuses
 * @property-read Collection<int, FirewallStatus> $firewallStatuses
 * @property-read Collection<int, LoginActivity> $loginActivities
 * @property-read Collection<int, UsbActivity> $usbActivities
 * @property-read Collection<int, WindowsService> $windowsServices
 * @property-read Collection<int, WindowsUpdate> $windowsUpdates
 * @property-read Collection<int, EventLog> $eventLogs
 * @property-read Collection<int, StartupProgram> $startupPrograms
 * @property-read Collection<int, Alert> $alerts
 * @property-read Collection<int, AuditLog> $auditLogs
 * @property-read Collection<int, MachineToken> $machineTokens
 * @property-read Collection<int, DeviceEvent> $deviceEvents
 * @property-read Collection<int, MachineConnectedDevice> $connectedDevices
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static online()
 * @method static \Illuminate\Database\Eloquent\Builder|static offline()
 * @method static \Illuminate\Database\Eloquent\Builder|static byCompany(int $companyId)
 */
class Machine extends Model
{
    use HasFactory;

    protected $table = 'machines';

    protected $fillable = [
        'company_id',
        'user_id',
        'machine_uid',
        'hostname',
        'device_name',
        'employee_mobile_number',
        'domain_name',
        'architecture',
        'operating_system',
        'os_version',
        'uptime_seconds',
        'current_logged_in_users',
        'manufacturer',
        'model',
        'serial_number',
        'bios_version',
        'processor',
        'ram_gb',
        'is_online',
        'last_heartbeat_at',
        'activation_token',
        'activated_at',
        'is_active',
        'status',
        'api_token',
    ];

    protected $appends = [
        'employee_name',
        'current_user',
        'cpu_model',
        'ip_address',
        'mac_address',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'is_active' => 'boolean',
        'last_heartbeat_at' => 'datetime',
        'activated_at' => 'datetime',
        'ram_gb' => 'integer',
    ];

    /**
     * Get the employee name from the assigned user (for LiveMonitoring display).
     *
     * @return string|null
     */
    public function getEmployeeNameAttribute(): ?string
    {
        return $this->assignedUser?->name;
    }

    /**
     * Get the current user assigned to the machine (for Overview tab display).
     *
     * @return string|null
     */
    public function getCurrentUserAttribute(): ?string
    {
        return $this->assignedUser?->name;
    }

    /**
     * Get the CPU model from the processor field (for Inventory display).
     *
     * @return string|null
     */
    public function getCpuModelAttribute(): ?string
    {
        return $this->processor;
    }

    /**
     * Get the first network adapter's IP address (for LiveMonitoring display).
     * Only returns a value if the networkAdapters relation is loaded.
     *
     * @return string|null
     */
    public function getIpAddressAttribute(): ?string
    {
        if (!$this->relationLoaded('networkAdapters')) {
            return null;
        }
        return $this->networkAdapters->first()?->ip_address;
    }

    /**
     * Get the first network adapter's MAC address (for LiveMonitoring display).
     * Only returns a value if the networkAdapters relation is loaded.
     *
     * @return string|null
     */
    public function getMacAddressAttribute(): ?string
    {
        if (!$this->relationLoaded('networkAdapters')) {
            return null;
        }
        return $this->networkAdapters->first()?->mac_address;
    }

    /**
     * Get the company that the machine belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user assigned to the machine.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the current status for the machine.
     *
     * @return HasOne<MachineCurrentStatus, $this>
     */
    public function currentStatus(): HasOne
    {
        return $this->hasOne(MachineCurrentStatus::class);
    }

    /**
     * Get the health logs for the machine.
     *
     * @return HasMany<HealthLog, $this>
     */
    public function healthLogs(): HasMany
    {
        return $this->hasMany(HealthLog::class);
    }

    /**
     * Get the hardware inventories for the machine.
     *
     * @return HasMany<HardwareInventory, $this>
     */
    public function hardwareInventories(): HasMany
    {
        return $this->hasMany(HardwareInventory::class);
    }

    /**
     * Get the software inventories for the machine.
     *
     * @return HasMany<SoftwareInventory, $this>
     */
    public function softwareInventories(): HasMany
    {
        return $this->hasMany(SoftwareInventory::class);
    }

    /**
     * Get the antivirus statuses for the machine.
     *
     * @return HasMany<AntivirusStatus, $this>
     */
    public function antivirusStatuses(): HasMany
    {
        return $this->hasMany(AntivirusStatus::class);
    }

    /**
     * Get the firewall statuses for the machine.
     *
     * @return HasMany<FirewallStatus, $this>
     */
    public function firewallStatuses(): HasMany
    {
        return $this->hasMany(FirewallStatus::class);
    }

    /**
     * Get the login activities for the machine.
     *
     * @return HasMany<LoginActivity, $this>
     */
    public function loginActivities(): HasMany
    {
        return $this->hasMany(LoginActivity::class);
    }

    /**
     * Get the USB activities for the machine.
     *
     * @return HasMany<UsbActivity, $this>
     */
    public function usbActivities(): HasMany
    {
        return $this->hasMany(UsbActivity::class);
    }

    /**
     * Get the Windows services for the machine.
     *
     * @return HasMany<WindowsService, $this>
     */
    public function windowsServices(): HasMany
    {
        return $this->hasMany(WindowsService::class);
    }

    /**
     * Get the Windows updates for the machine.
     *
     * @return HasMany<WindowsUpdate, $this>
     */
    public function windowsUpdates(): HasMany
    {
        return $this->hasMany(WindowsUpdate::class);
    }

    /**
     * Get the event logs for the machine.
     *
     * @return HasMany<EventLog, $this>
     */
    public function eventLogs(): HasMany
    {
        return $this->hasMany(EventLog::class);
    }

    /**
     * Get the startups programs for the machine.
     *
     * @return HasMany<StartupProgram, $this>
     */
    public function startupPrograms(): HasMany
    {
        return $this->hasMany(StartupProgram::class);
    }

    /**
     * Get the device events for the machine.
     *
     * @return HasMany<DeviceEvent, $this>
     */
    public function deviceEvents(): HasMany
    {
        return $this->hasMany(DeviceEvent::class);
    }

    /**
     * Get the network adapters for the machine.
     *
     * @return HasMany<MachineNetworkAdapter, $this>
     */
    public function networkAdapters(): HasMany
    {
        return $this->hasMany(MachineNetworkAdapter::class);
    }

    /**
     * Get the connected devices for the machine.
     *
     * @return HasMany<MachineConnectedDevice, $this>
     */
    public function connectedDevices(): HasMany
    {
        return $this->hasMany(MachineConnectedDevice::class);
    }

    /**
     * Get the alerts for the machine.
     *
     * @return HasMany<Alert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Get the audit logs for the machine.
     *
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the machine tokens for the machine.
     *
     * @return HasMany<MachineToken, $this>
     */
    public function machineTokens(): HasMany
    {
        return $this->hasMany(MachineToken::class);
    }

    /**
     * Find a machine by its api_token.
     *
     * @param string $apiToken
     * @return Machine|null
     */
    public static function findByApiToken(string $apiToken): ?self
    {
        return static::where('api_token', $apiToken)->first();
    }

    /**
     * Scope a query to only include online machines.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOnline($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_online', true);
    }

    /**
     * Scope a query to only include offline machines.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOffline($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_online', false);
    }

    /**
     * Scope a query to only include machines belonging to a specific company.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @param  int  $companyId
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeByCompany($query, int $companyId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('company_id', $companyId);
    }
}
