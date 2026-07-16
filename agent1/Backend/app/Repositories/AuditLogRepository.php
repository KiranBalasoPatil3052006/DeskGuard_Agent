<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class AuditLogRepository
 *
 * Repository for AuditLog-related database operations.
 * Extends BaseRepository with audit-log-specific query methods.
 *
 * @package App\Repositories
 */
class AuditLogRepository extends BaseRepository
{
    /**
     * AuditLogRepository constructor.
     *
     * @param AuditLog $auditLog The AuditLog model instance.
     */
    public function __construct(AuditLog $auditLog)
    {
        parent::__construct($auditLog);
    }

    /**
     * Retrieve audit logs for a specific company.
     *
     * @param int $companyId The company ID.
     * @param int $limit     The maximum number of records to return.
     * @return Collection A collection of audit log entries.
     */
    public function getByCompany(int $companyId, int $limit = 50): Collection
    {
        try {
            return $this->model
                ->where('company_id', '=', $companyId)
                ->latest('created_at')
                ->limit($limit)
                ->get();
        } catch (\Throwable $e) {
            Log::error('AuditLogRepository::getByCompany - Failed to retrieve audit logs by company', [
                'companyId' => $companyId,
                'limit'     => $limit,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve audit logs for a specific user.
     *
     * @param int $userId The user ID.
     * @return Collection A collection of audit log entries for the user.
     */
    public function getByUser(int $userId): Collection
    {
        try {
            return $this->model
                ->where('user_id', '=', $userId)
                ->latest('created_at')
                ->get();
        } catch (\Throwable $e) {
            Log::error('AuditLogRepository::getByUser - Failed to retrieve audit logs by user', [
                'userId' => $userId,
                'error'  => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve audit logs filtered by event type.
     *
     * @param string $eventType The event type to filter by.
     * @return Collection A collection of audit log entries matching the event type.
     */
    public function getByEventType(string $eventType): Collection
    {
        try {
            return $this->model
                ->where('event_type', '=', $eventType)
                ->latest('created_at')
                ->get();
        } catch (\Throwable $e) {
            Log::error('AuditLogRepository::getByEventType - Failed to retrieve audit logs by event type', [
                'eventType' => $eventType,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve audit logs within a specified date range.
     *
     * @param string $from The start date/time string.
     * @param string $to   The end date/time string.
     * @return Collection A collection of audit log entries in the given range.
     */
    public function getByDateRange(string $from, string $to): Collection
    {
        try {
            return $this->model
                ->whereBetween('created_at', [$from, $to])
                ->latest('created_at')
                ->get();
        } catch (\Throwable $e) {
            Log::error('AuditLogRepository::getByDateRange - Failed to retrieve audit logs by date range', [
                'from'  => $from,
                'to'    => $to,
                'error' => $e->getMessage(),
            ]);
            return new Collection();
        }
    }
}
