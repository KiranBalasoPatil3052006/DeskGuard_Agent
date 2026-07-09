<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $machine_id
 * @property string|null $device_name
 * @property string|null $device_type
 * @property string|null $manufacturer
 * @property string|null $connection_type
 * @property string $event_type
 * @property Carbon $event_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Machine $machine
 */
class DeviceEvent extends Model
{
    protected $table = 'device_events';

    protected $fillable = [
        'machine_id',
        'device_name',
        'device_type',
        'manufacturer',
        'connection_type',
        'event_type',
        'event_time',
    ];

    protected $casts = [
        'event_time' => 'datetime',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
