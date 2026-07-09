<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class HealthLog
 *
 * @property int $id
 * @property int|null $company_id
 * @property int $machine_id
 * @property float|null $cpu_percentage
 * @property float|null $cpu_temperature
 * @property float|null $ram_percentage
 * @property int|null $ram_used_bytes
 * @property int|null $ram_available_bytes
 * @property int|null $ram_total_bytes
 * @property float|null $disk_percentage
 * @property int|null $disk_free_bytes
 * @property int|null $disk_total_bytes
 * @property int|null $network_sent_bytes
 * @property int|null $network_received_bytes
 * @property float|null $battery_percentage
 * @property bool|null $online_status
 * @property Carbon $collected_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Company|null $company
 * @property-read Machine $machine
 */
class HealthLog extends Model
{
    use HasFactory;

    protected $table = 'health_logs';

    protected $fillable = [
        'company_id',
        'machine_id',
        'cpu_percentage',
        'cpu_temperature',
        'ram_percentage',
        'ram_used_bytes',
        'ram_available_bytes',
        'ram_total_bytes',
        'disk_percentage',
        'disk_free_bytes',
        'disk_total_bytes',
        'network_sent_bytes',
        'network_received_bytes',
        'battery_percentage',
        'online_status',
        'collected_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'online_status' => 'boolean',
    ];

    /**
     * Get the company that the health log belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the machine that the health log belongs to.
     *
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
