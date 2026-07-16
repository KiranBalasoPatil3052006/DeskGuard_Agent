<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawPayloadLog extends Model
{
    use HasFactory;

    protected $table = 'raw_payload_logs';

    protected $fillable = [
        'machine_id',
        'machine_uid',
        'payload',
        'source_ip',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
