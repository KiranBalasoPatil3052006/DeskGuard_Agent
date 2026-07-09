<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class HardwareInventory
 *
 * @property int $id
 * @property int|null $company_id
 * @property int $machine_id
 * @property string|null $manufacturer
 * @property string|null $model
 * @property string|null $serial_number
 * @property string|null $bios_version
 * @property string|null $processor_name
 * @property int|null $processor_cores
 * @property int|null $processor_threads
 * @property float|null $processor_clock_speed
 * @property float|null $ram_total_gb
 * @property string|null $ram_type
 * @property string|null $disk_model
 * @property string|null $disk_type
 * @property float|null $disk_size_gb
 * @property string|null $gpu_name
 * @property Carbon|null $collected_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Company|null $company
 * @property-read Machine $machine
 */
class HardwareInventory extends Model
{
    use HasFactory;

    protected $table = 'hardware_inventory';

    protected $fillable = [
        'company_id',
        'machine_id',
        'manufacturer',
        'model',
        'serial_number',
        'bios_version',
        'bios_vendor',
        'bios_release_date',
        'processor_name',
        'processor_cores',
        'processor_threads',
        'processor_clock_speed',
        'system_architecture',
        'ram_total_gb',
        'ram_type',
        'disk_model',
        'disk_type',
        'disk_size_gb',
        'gpu_name',
        'collected_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
    ];

    /**
     * Get the company that the hardware inventory belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the machine that the hardware inventory belongs to.
     *
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
