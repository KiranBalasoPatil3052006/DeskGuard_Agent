<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $mobile_number
 * @property string $otp
 * @property Carbon $expires_at
 * @property bool $is_used
 * @property Carbon|null $used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OtpCode extends Model
{
    protected $table = 'otp_codes';

    protected $fillable = [
        'mobile_number',
        'otp',
        'expires_at',
        'is_used',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    public function scopeValid($query)
    {
        return $query->where('is_used', false)
            ->where('expires_at', '>', now());
    }

    public function scopeForMobile($query, string $mobile)
    {
        return $query->where('mobile_number', $mobile);
    }

    public function isValid(): bool
    {
        return !$this->is_used && $this->expires_at->isFuture();
    }
}
