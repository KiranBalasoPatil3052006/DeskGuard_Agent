<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class SoftwareInventory
 *
 * @property int $id
 * @property int|null $company_id
 * @property int $machine_id
 * @property string|null $software_name
 * @property string|null $version
 * @property string|null $publisher
 * @property Carbon|null $install_date
 * @property Carbon|null $collected_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Company|null $company
 * @property-read Machine $machine
 */
class SoftwareInventory extends Model
{
    use HasFactory;

    protected $table = 'software_inventory';

    protected $fillable = [
        'company_id',
        'machine_id',
        'software_name',
        'version',
        'publisher',
        'install_date',
        'architecture',
        'registry_key_path',
        'estimated_size_mb',
        'collected_at',
    ];

    protected $casts = [
        'install_date' => 'date',
        'collected_at' => 'datetime',
    ];

    /**
     * Get the company that the software inventory belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the machine that the software inventory belongs to.
     *
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
