<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class StartupProgram
 *
 * @property int $id
 * @property int|null $company_id
 * @property int $machine_id
 * @property string|null $program_name
 * @property string|null $program_path
 * @property string|null $registry_key
 * @property string|null $startup_type
 * @property string|null $status
 * @property Carbon|null $collected_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Company|null $company
 * @property-read Machine $machine
 */
class StartupProgram extends Model
{
    use HasFactory;

    protected $table = 'startup_programs';

    protected $fillable = [
        'company_id',
        'machine_id',
        'program_name',
        'program_path',
        'registry_key',
        'startup_type',
        'status',
        'collected_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
    ];

    /**
     * Get the company that the startup program belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the machine that the startup program belongs to.
     *
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
