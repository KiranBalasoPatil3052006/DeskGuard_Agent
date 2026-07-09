<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\HealthLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class HealthLogRepository
 *
 * Repository for HealthLog-related database operations.
 * Extends BaseRepository with health-log-specific query methods.
 *
 * @package App\Repositories
 */
class HealthLogRepository extends BaseRepository
{
    /**
     * HealthLogRepository constructor.
     *
     * @param HealthLog $healthLog The HealthLog model instance.
     */
    public function __construct(HealthLog $healthLog)
    {
        parent::__construct($healthLog);
    }

    /**
     * Retrieve the most recent health log entry for a given machine.
     *
     * @param int $machineId The machine ID.
     * @return HealthLog|null The latest health log instance if found, null otherwise.
     */
    public function getLatestByMachine(int $machineId): ?HealthLog
    {
        try {
            return $this->model
                ->where('machine_id', '=', $machineId)
                ->latest('created_at')
                ->first();
        } catch (\Throwable $e) {
            Log::error('HealthLogRepository::getLatestByMachine - Failed to retrieve latest health log', [
                'machineId' => $machineId,
                'error'     => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Retrieve health log history for a given machine within a date range.
     *
     * @param int    $machineId The machine ID.
     * @param string $from      The start date/time string.
     * @param string $to        The end date/time string.
     * @return Collection A collection of health log records in the given range.
     */
    public function getHistory(int $machineId, string $from, string $to): Collection
    {
        try {
            return $this->model
                ->where('machine_id', '=', $machineId)
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at', 'asc')
                ->get();
        } catch (\Throwable $e) {
            Log::error('HealthLogRepository::getHistory - Failed to retrieve health log history', [
                'machineId' => $machineId,
                'from'      => $from,
                'to'        => $to,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve the most recent health logs for all machines in a company.
     *
     * @param int $companyId The company ID.
     * @param int $limit     The maximum number of records to return.
     * @return Collection A collection of the latest health log entries.
     */
    public function getLatestByCompany(int $companyId, int $limit = 100): Collection
    {
        try {
            return $this->model
                ->whereHas('machine', function ($query) use ($companyId) {
                    $query->where('company_id', '=', $companyId);
                })
                ->latest('created_at')
                ->limit($limit)
                ->get();
        } catch (\Throwable $e) {
            Log::error('HealthLogRepository::getLatestByCompany - Failed to retrieve latest health logs by company', [
                'companyId' => $companyId,
                'limit'     => $limit,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve CPU usage trend data for a given machine over a specified number of hours.
     *
     * @param int $machineId The machine ID.
     * @param int $hours     The number of hours to look back.
     * @return Collection A collection of health log records with CPU data.
     */
    public function getCpuTrend(int $machineId, int $hours = 24): Collection
    {
        try {
            $from = now()->subHours($hours);

            return $this->model
                ->where('machine_id', '=', $machineId)
                ->where('created_at', '>=', $from)
                ->orderBy('created_at', 'asc')
                ->get(['created_at', 'cpu_percentage', 'cpu_temperature']);
        } catch (\Throwable $e) {
            Log::error('HealthLogRepository::getCpuTrend - Failed to retrieve CPU trend', [
                'machineId' => $machineId,
                'hours'     => $hours,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve RAM usage trend data for a given machine over a specified number of hours.
     *
     * @param int $machineId The machine ID.
     * @param int $hours     The number of hours to look back.
     * @return Collection A collection of health log records with RAM data.
     */
    public function getRamTrend(int $machineId, int $hours = 24): Collection
    {
        try {
            $from = now()->subHours($hours);

            return $this->model
                ->where('machine_id', '=', $machineId)
                ->where('created_at', '>=', $from)
                ->orderBy('created_at', 'asc')
                ->get(['created_at', 'ram_percentage', 'ram_used_bytes', 'ram_total_bytes']);
        } catch (\Throwable $e) {
            Log::error('HealthLogRepository::getRamTrend - Failed to retrieve RAM trend', [
                'machineId' => $machineId,
                'hours'     => $hours,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }
}
