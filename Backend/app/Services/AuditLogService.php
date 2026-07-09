<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Machine;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class AuditLogService
 *
 * Provides a centralised mechanism for recording audit log entries
 * across all services. Each entry captures the event type, description,
 * old and new values, and the responsible user or machine.
 *
 * @package App\Services
 */
class AuditLogService
{
    /**
     * AuditLogService constructor.
     */
    public function __construct()
    {
    }

    /**
     * Record an audit log entry.
     *
     * @param  string       $eventType   The type of event (e.g. 'login', 'create', 'update', 'delete').
     * @param  string       $description A human-readable description of the event.
     * @param  array|null   $oldValues   The state before the change (for updates/deletes).
     * @param  array|null   $newValues   The state after the change (for creates/updates).
     * @param  User|null    $user        The user who performed the action.
     * @param  Machine|null $machine     The machine involved in the action.
     * @return AuditLog
     */
    public function log(
        string $eventType,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null,
        ?Machine $machine = null
    ): AuditLog {
        try {
            $request = request();

            $auditLog = AuditLog::create([
                'company_id'  => $machine?->company_id ?? $user?->company_id ?? ($newValues['company_id'] ?? null),
                'user_id'     => $user?->id ?? auth()->id(),
                'machine_id'  => $machine?->id ?? ($newValues['id'] ?? null),
                'event_type'  => $eventType,
                'description' => $description,
                'old_values'  => $oldValues,
                'new_values'  => $newValues,
                'ip_address'  => $request?->ip(),
                'user_agent'  => $request?->userAgent(),
            ]);

            Log::debug('Audit log entry created', [
                'audit_log_id' => $auditLog->id,
                'event_type'   => $eventType,
                'description'  => $description,
            ]);

            return $auditLog;
        } catch (Exception $e) {
            Log::error('AuditLogService::log - Failed to create audit log entry', [
                'event_type'  => $eventType,
                'description' => $description,
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Retrieve audit logs for a company, optionally filtered by event type and date range.
     *
     * @param  int         $companyId
     * @param  string|null $eventType
     * @param  string|null $from
     * @param  string|null $to
     * @return Collection<int, AuditLog>
     */
    public function getLogs(int $companyId, ?string $eventType = null, ?string $from = null, ?string $to = null): Collection
    {
        try {
            $query = AuditLog::with(['user', 'machine'])
                ->where('company_id', $companyId);

            if ($eventType !== null) {
                $query->where('event_type', $eventType);
            }

            if ($from !== null) {
                $query->where('created_at', '>=', $from);
            }

            if ($to !== null) {
                $query->where('created_at', '<=', $to);
            }

            return $query->orderBy('created_at', 'desc')->get();
        } catch (Exception $e) {
            Log::error('AuditLogService::getLogs - Failed to retrieve audit logs', [
                'company_id' => $companyId,
                'event_type' => $eventType,
                'from'       => $from,
                'to'         => $to,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
