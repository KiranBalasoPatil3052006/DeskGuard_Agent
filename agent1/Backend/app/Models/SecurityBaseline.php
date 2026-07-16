<?php

/**
 * SecurityBaseline Model
 *
 * Represents the approved security state for a machine.
 * Stores baseline values for antivirus status, firewall status,
 * and other security posture indicators.
 *
 * Used by the change detection system to identify security drifts
 * such as antivirus being disabled or firewall being turned off.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int $machine_id
 * @property string $component
 * @property string|null $value
 * @property string $baselined_at
 * @property-read Machine $machine
 * @property-read Company $company
 */
class SecurityBaseline extends Model
{
    use HasFactory;

    /** @var string The table associated with the model. */
    protected $table = 'security_baselines';

    /** @var array The attributes that are mass assignable. */
    protected $fillable = [
        'company_id',
        'machine_id',
        'component',
        'value',
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
