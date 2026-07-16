<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int $machine_id
 * @property string $category
 * @property string $change_type
 * @property string $severity
 * @property string $status
 * @property string|null $recommendation
 * @property string|null $item_identifier
 * @property string|null $item_label
 * @property string|null $previous_value
 * @property string|null $new_value
 * @property string|null $description
 * @property array|null $metadata
 * @property string $detected_at
 * @property-read Machine $machine
 * @property-read Company $company
 *
 * @method static Builder|static byCategory(string $category)
 * @method static Builder|static byMachine(int $machineId)
 * @method static Builder|static recent(int $days = 7)
 * @method static Builder|static bySeverity(string $severity)
 * @method static Builder|static byStatus(string $status)
 */
class ChangeHistory extends Model
{
    use HasFactory;

    protected $table = 'change_history';

    protected $fillable = [
        'company_id',
        'machine_id',
        'category',
        'change_type',
        'severity',
        'status',
        'recommendation',
        'item_identifier',
        'item_label',
        'previous_value',
        'new_value',
        'description',
        'metadata',
        'detected_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'detected_at' => 'datetime',
    ];

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Get a recommendation for this change based on category and change type.
     */
    public function getRecommendationAttribute(): ?string
    {
        $cat = strtolower($this->category ?? '');
        $type = strtolower($this->change_type ?? '');

        return match (true) {
            $cat === 'hardware' && $type === 'modified' => 'Verify customer authorization for hardware replacement. Check if replacement was approved by AMC contract.',
            $cat === 'hardware' && $type === 'removed' => 'Hardware component was removed. Verify if removal was authorized and update asset inventory.',
            $cat === 'hardware' && $type === 'added' => 'New hardware component detected. Verify if addition was authorized.',
            $cat === 'security' && in_array($type, ['disabled', 'modified']) => 'Immediately investigate security setting change. Contact user to verify if intentional. Re-enable protection if unauthorized.',
            $cat === 'software' && $type === 'removed' => 'Verify if software removal was authorized. Unauthorized removal requires immediate action.',
            $cat === 'software' && $type === 'added' => 'New software installed. Verify if installation was authorized by company policy.',
            $cat === 'network' && $type === 'modified' => 'Network configuration change detected. MAC address changes may indicate hardware replacement.',
            $cat === 'peripheral' && $type === 'disconnected' => 'Peripheral device was disconnected. Verify if removal was authorized and update asset inventory.',
            $cat === 'peripheral' && $type === 'connected' => 'New peripheral device connected. Verify device for security compliance.',
            $cat === 'configuration' && $type === 'modified' => 'System configuration was modified. Verify if change was authorized and matches expected configuration.',
            default => 'Review the change details and verify with the customer if this change was authorized.',
        };
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeByMachine(Builder $query, int $machineId): Builder
    {
        return $query->where('machine_id', $machineId);
    }

    public function scopeRecentChange(Builder $query, int $days = 7): Builder
    {
        return $query->where('detected_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to only include changes with a specific severity level.
     *
     * @param Builder $query The query builder instance.
     * @param string $severity The severity level to filter by.
     * @return Builder
     */
    public function scopeBySeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }
}