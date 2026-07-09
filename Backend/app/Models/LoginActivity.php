<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class LoginActivity
 *
 * @property int $id
 * @property int|null $company_id
 * @property int $machine_id
 * @property string|null $username
 * @property string|null $event_type
 * @property string|null $session_id
 * @property Carbon|null $logon_time
 * @property Carbon|null $logoff_time
 * @property Carbon|null $collected_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Company|null $company
 * @property-read Machine $machine
 */
class LoginActivity extends Model
{
    use HasFactory;

    protected $table = 'login_activities';

    protected $appends = [
        'is_success',
    ];

    protected $fillable = [
        'company_id',
        'machine_id',
        'event_type',
        'username',
        'session_id',
        'logon_time',
        'logoff_time',
        'collected_at',
    ];

    protected $casts = [
        'logon_time' => 'datetime',
        'logoff_time' => 'datetime',
        'collected_at' => 'datetime',
    ];

    /**
     * Determine if the login activity was successful based on event_type.
     *
     * @return bool|null
     */
    public function getIsSuccessAttribute(): ?bool
    {
        $successTypes = [
            'Logon',
            'Logoff',
            'Logoff (Initiative)',
            'Session Reconnected',
            'Session Disconnected',
            'Workstation Unlocked',
            'Logon (Explicit Credentials)',
            'Admin Logon (Special)',
        ];

        if (in_array($this->event_type, $successTypes, true)) {
            return true;
        }

        if ($this->event_type === 'Failed Logon') {
            return false;
        }

        return null;
    }

    /**
     * Get the company that the login activity belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the machine that the login activity belongs to.
     *
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
