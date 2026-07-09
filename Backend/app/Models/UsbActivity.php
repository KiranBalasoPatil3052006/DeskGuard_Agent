<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class UsbActivity
 *
 * @property int $id
 * @property int|null $company_id
 * @property int $machine_id
 * @property string|null $device_name
 * @property string|null $device_serial
 * @property string|null $drive_letter
 * @property string|null $event_type
 * @property Carbon|null $collected_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Company|null $company
 * @property-read Machine $machine
 */
class UsbActivity extends Model
{
    use HasFactory;

    protected $table = 'usb_activities';

    protected $fillable = [
        'company_id',
        'machine_id',
        'device_name',
        'device_serial',
        'drive_letter',
        'event_type',
        'collected_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
    ];

    /**
     * Get the company that the USB activity belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the machine that the USB activity belongs to.
     *
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
