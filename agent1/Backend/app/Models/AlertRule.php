<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class AlertRule
 *
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string|null $description
 * @property string|null $metric
 * @property string|null $operator
 * @property string|null $value
 * @property string|null $severity
 * @property int|null $duration_minutes
 * @property bool $is_enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read Company|null $company
 * @property-read Collection<int, Alert> $alerts
 */
class AlertRule extends Model
{
    use HasFactory;

    protected $table = 'alert_rules';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'metric',
        'operator',
        'value',
        'severity',
        'duration_minutes',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Get the company that the alert rule belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the alerts for the alert rule.
     *
     * @return HasMany<Alert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
