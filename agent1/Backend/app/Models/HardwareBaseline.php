<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int $machine_id
 * @property string $component
 * @property string|null $manufacturer
 * @property string|null $model
 * @property string|null $serial_number
 * @property string|null $capacity
 * @property string|null $speed
 * @property string|null $slot_info
 * @property array|null $properties
 * @property string $baselined_at
 * @property-read Machine $machine
 * @property-read Company $company
 */
class HardwareBaseline extends Model
{
    use HasFactory;

    protected $table = 'hardware_baselines';

    protected $fillable = [
        'company_id',
        'machine_id',
        'component',
        'manufacturer',
        'model',
        'serial_number',
        'capacity',
        'speed',
        'slot_info',
        'properties',
        'baselined_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'baselined_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}