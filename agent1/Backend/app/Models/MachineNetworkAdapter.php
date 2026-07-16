<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineNetworkAdapter extends Model
{
    use HasFactory;

    protected $table = 'machine_network_adapters';

    protected $fillable = [
        'machine_id',
        'adapter_name',
        'ip_address',
        'ip_address_v6',
        'mac_address',
        'adapter_type',
        'speed',
        'bytes_sent',
        'bytes_received',
        'status',
        'updated_at',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
