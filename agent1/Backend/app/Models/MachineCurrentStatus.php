<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class MachineCurrentStatus
 *
 * @property int $id
 * @property int $machine_id
 * @property int|null $company_id
 * @property float|null $cpu_percentage
 * @property float|null $cpu_temperature
 * @property float|null $ram_percentage
 * @property int|null $ram_used_bytes
 * @property int|null $ram_available_bytes
 * @property int|null $ram_total_bytes
 * @property float|null $disk_percentage
 * @property int|null $disk_free_bytes
 * @property int|null $disk_total_bytes
 * @property float|null $battery_percentage
 * @property bool|null $online_status
 * @property \Illuminate\Support\Carbon|null $collected_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read Machine $machine
 * @property-read Company|null $company
 */
class MachineCurrentStatus extends Model
{
    use HasFactory;

    protected $table = 'machine_current_status';

    protected $fillable = [
        'machine_id',
        'company_id',
        'cpu_percentage',
        'cpu_temperature',
        'cpu_clock_speed',
        'cpu_core_count',
        'ram_percentage',
        'ram_used_bytes',
        'ram_available_bytes',
        'ram_total_bytes',
        'disk_percentage',
        'disk_free_bytes',
        'disk_total_bytes',
        'disk_used_bytes',
        'disk_health_status',
        'battery_percentage',
        'battery_charging_status',
        'battery_wear_level',
        'battery_is_present',
        'battery_design_capacity',
        'battery_full_charge_capacity',
        'network_received_bytes',
        'network_sent_bytes',
        'online_status',
        'last_collected_at',
        'antivirus_status',
        'firewall_status',
        'pending_updates',
        'collected_at',
    ];

    protected $casts = [
        'cpu_percentage'          => 'decimal:2',
        'cpu_temperature'         => 'decimal:2',
        'cpu_clock_speed'         => 'decimal:2',
        'cpu_core_count'          => 'integer',
        'ram_percentage'          => 'decimal:2',
        'ram_used_bytes'          => 'integer',
        'ram_available_bytes'     => 'integer',
        'ram_total_bytes'         => 'integer',
        'disk_percentage'         => 'decimal:2',
        'disk_free_bytes'         => 'integer',
        'disk_total_bytes'        => 'integer',
        'disk_used_bytes'         => 'integer',
        'battery_percentage'      => 'decimal:2',
        'battery_charging_status' => 'boolean',
        'battery_wear_level'      => 'decimal:2',
        'network_received_bytes'  => 'integer',
        'network_sent_bytes'      => 'integer',
        'online_status'           => 'boolean',
        'last_collected_at'       => 'datetime',
        'collected_at'            => 'datetime',
    ];

    /**
     * Get the machine that the status belongs to.
     *
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * Get the company that the status belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
