<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessLog extends Model
{
    use HasFactory;

    protected $table = 'process_logs';

    protected $fillable = [
        'machine_id',
        'process_name',
        'process_id',
        'executable_path',
        'thread_count',
        'user_name',
        'cpu_usage',
        'memory_usage',
        'collected_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
