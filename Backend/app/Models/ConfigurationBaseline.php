<?php

/**
 * ConfigurationBaseline Model
 *
 * Represents the approved configuration state for a machine.
 * Stores baseline values for OS settings, startup programs,
 * service configurations, and other system settings.
 *
 * Used by the change detection system to identify unauthorized
 * configuration changes such as services being stopped or
 * startup programs being disabled.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int $machine_id
 * @property string $setting_key
 * @property string|null $setting_value
 * @property string $baselined_at
 * @property-read Machine $machine
 * @property-read Company $company
 */
class ConfigurationBaseline extends Model
{
    use HasFactory;

    /** @var string The table associated with the model. */
    protected $table = 'configuration_baselines';

    /** @var array The attributes that are mass assignable. */
    protected $fillable = [
        'company_id',
        'machine_id',
        'setting_key',
        'setting_value',
        'baselined_at',
    ];

    /** @var array The attributes that should be cast. */
    protected $casts = [
        'baselined_at' => 'datetime',
    ];

    /**
     * Get the company that owns this baseline.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the machine that owns this baseline.
     *
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
