<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Alert
 *
 * @property int $id
 * @property int|null $company_id
 * @property int|null $machine_id
 * @property int|null $alert_rule_id
 * @property string $severity
 * @property string $title
 * @property string|null $description
 * @property mixed|null $metadata
 * @property string|null $status
 * @property int|null $acknowledged_by
 * @property int|null $resolved_by
 * @property \Illuminate\Support\Carbon|null $acknowledged_at
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read Company|null $company
 * @property-read Machine|null $machine
 * @property-read AlertRule|null $alertRule
 * @property-read User|null $acknowledgedBy
 * @property-read User|null $resolvedBy
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static open()
 * @method static \Illuminate\Database\Eloquent\Builder|static critical()
 * @method static \Illuminate\Database\Eloquent\Builder|static byCompany(int $companyId)
 */
class Alert extends Model
{
    use HasFactory;

    protected $table = 'alerts';

    protected $fillable = [
        'company_id',
        'machine_id',
        'device_name',
        'alert_rule_id',
        'severity',
        'title',
        'description',
        'metadata',
        'status',
        'acknowledged_by',
        'resolved_by',
        'acknowledged_at',
        'resolved_at',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    /**
     * Get the company that the alert belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the machine that the alert belongs to.
     *
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * Get the alert rule that triggered the alert.
     *
     * @return BelongsTo<AlertRule, $this>
     */
    public function alertRule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class);
    }

    /**
     * Get the user who acknowledged the alert.
     *
     * @return BelongsTo<User, $this>
     */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Get the user who resolved the alert.
     *
     * @return BelongsTo<User, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope a query to only include open alerts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOpen($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereIn('status', ['open', 'acknowledged']);
    }

    /**
     * Scope a query to only include critical alerts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeCritical($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope a query to only include alerts for a specific company.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @param  int  $companyId
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeByCompany($query, int $companyId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('company_id', $companyId);
    }
}
