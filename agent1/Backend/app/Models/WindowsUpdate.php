<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class WindowsUpdate
 *
 * @property int $id
 * @property int|null $company_id
 * @property int $machine_id
 * @property string|null $update_title
 * @property string|null $update_description
 * @property string|null $kb_article
 * @property string|null $category
 * @property string|null $severity
 * @property bool|null $is_installed
 * @property Carbon|null $collected_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Company|null $company
 * @property-read Machine $machine
 */
class WindowsUpdate extends Model
{
    use HasFactory;

    protected $table = 'windows_updates';

    protected $fillable = [
        'company_id',
        'machine_id',
        'update_title',
        'update_description',
        'kb_article',
        'category',
        'severity',
        'is_installed',
        'collected_at',
    ];

    protected $casts = [
        'is_installed' => 'boolean',
        'collected_at' => 'datetime',
    ];

    /**
     * Get the company that the Windows update belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the machine that the Windows update belongs to.
     *
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
