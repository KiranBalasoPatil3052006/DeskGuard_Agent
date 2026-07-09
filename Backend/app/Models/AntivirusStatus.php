<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class AntivirusStatus
 *
 * @property int $id
 * @property int|null $company_id
 * @property int $machine_id
 * @property string|null $display_name
 * @property bool|null $is_enabled
 * @property bool|null $is_updated
 * @property bool|null $real_time_protection
 * @property string|null $definition_status
 * @property Carbon|null $collected_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Company|null $company
 * @property-read Machine $machine
 */
class AntivirusStatus extends Model
{
    use HasFactory;

    protected $table = 'antivirus_status';

    protected $fillable = [
        'company_id',
        'machine_id',
        'display_name',
        'is_enabled',
        'is_updated',
        'real_time_protection',
        'definition_status',
        'collected_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_updated' => 'boolean',
        'real_time_protection' => 'boolean',
        'collected_at' => 'datetime',
    ];

    /**
     * Get the company that the antivirus status belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the machine that the antivirus status belongs to.
     *
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
