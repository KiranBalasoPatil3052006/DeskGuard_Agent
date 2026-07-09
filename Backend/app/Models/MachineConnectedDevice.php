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
 * @property string $status
 * @property Carbon|null $last_seen
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Machine $machine
 */
class MachineConnectedDevice extends Model
{
    protected $table = 'machine_connected_devices';

    protected $fillable = [
        'machine_id',
        'device_name',
        'device_type',
        'manufacturer',
        'connection_type',
        'status',
        'last_seen',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
