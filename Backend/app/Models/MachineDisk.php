<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineDisk extends Model
{
    use HasFactory;

    protected $table = 'machine_disks';

    protected $fillable = [
        'machine_id',
        'drive_letter',
        'volume_label',
        'total_gb',
        'used_gb',
        'free_gb',
        'file_system',
        'drive_type',
        'health_status',
        'updated_at',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
