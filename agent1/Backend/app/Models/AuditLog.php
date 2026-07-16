<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AuditLog
 *
 * @property int $id
 * @property int|null $company_id
 * @property int|null $user_id
 * @property int|null $machine_id
 * @property string $event_type
 * @property string|null $description
 * @property mixed|null $old_values
 * @property mixed|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 *
 * @property-read Company|null $company
 * @property-read User|null $user
 * @property-read Machine|null $machine
 */
class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'user_id',
        'machine_id',
        'event_type',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    /**
     * Get the company that the audit log belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user that the audit log belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the machine that the audit log belongs to.
     *
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
