<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class WindowsService
 *
 * @property int $id
 * @property int|null $company_id
 * @property int $machine_id
 * @property string|null $service_name
 * @property string|null $display_name
 * @property string|null $status
 * @property string|null $start_type
 * @property Carbon|null $collected_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Company|null $company
 * @property-read Machine $machine
 */
class WindowsService extends Model
{
    use HasFactory;

    protected $table = 'windows_services';

    protected $fillable = [
        'company_id',
        'machine_id',
        'service_name',
        'display_name',
        'status',
        'start_type',
        'service_type',
        'log_on_as',
        'collected_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
    ];

    /**
     * Get the company that the Windows service belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the machine that the Windows service belongs to.
     *
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
