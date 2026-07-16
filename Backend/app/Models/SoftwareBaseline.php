<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int $machine_id
 * @property string $software_name
 * @property string|null $version
 * @property string|null $publisher
 * @property string|null $architecture
 * @property string $baselined_at
 * @property-read Machine $machine
 * @property-read Company $company
 */
class SoftwareBaseline extends Model
{
    use HasFactory;

    protected $table = 'software_baselines';

    protected $fillable = [
        'company_id',
        'machine_id',
        'software_name',
        'version',
        'publisher',
        'architecture',
        'baselined_at',
    ];

    protected $casts = [
        'baselined_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}