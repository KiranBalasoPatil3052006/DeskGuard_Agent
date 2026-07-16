<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Report
 *
 * @property int $id
 * @property int|null $company_id
 * @property int|null $generated_by
 * @property string $name
 * @property string|null $type
 * @property string|null $file_path
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property mixed|null $filters
 * @property Carbon|null $generated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Company|null $company
 * @property-read User|null $generator
 */
class Report extends Model
{
    use HasFactory;

    protected $table = 'reports';

    protected $fillable = [
        'company_id',
        'generated_by',
        'name',
        'type',
        'file_path',
        'mime_type',
        'file_size',
        'filters',
        'generated_at',
    ];

    protected $casts = [
        'filters' => 'json',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the company that the report belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who generated the report.
     *
     * @return BelongsTo<User, $this>
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
