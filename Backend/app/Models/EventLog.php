<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class EventLog
 *
 * @property int $id
 * @property int|null $company_id
 * @property int $machine_id
 * @property string|null $event_id
 * @property string|null $log_name
 * @property string|null $source
 * @property string|null $level
 * @property string|null $message
 * @property Carbon|null $event_time
 * @property Carbon|null $collected_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Company|null $company
 * @property-read Machine $machine
 */
class EventLog extends Model
{
    use HasFactory;

    protected $table = 'event_logs';

    protected $fillable = [
        'company_id',
        'machine_id',
        'event_id',
        'log_name',
        'source',
        'level',
        'message',
        'event_time',
        'collected_at',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'collected_at' => 'datetime',
    ];

    /**
     * Get the company that the event log belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the machine that the event log belongs to.
     *
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
