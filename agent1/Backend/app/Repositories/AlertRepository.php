<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Alert;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class AlertRepository
 *
 * Repository for Alert-related database operations.
 * Extends BaseRepository with alert-specific query methods.
 *
 * @package App\Repositories
 */
class AlertRepository extends BaseRepository
{
    /**
     * AlertRepository constructor.
     *
     * @param Alert $alert The Alert model instance.
     */
    public function __construct(Alert $alert)
    {
        parent::__construct($alert);
    }

    /**
     * Retrieve all open (unresolved) alerts for a specific company.
     *
     * @param int $companyId The company ID.
     * @return Collection A collection of open alerts.
     */
    public function findOpenByCompany(int $companyId): Collection
    {
        try {
            return $this->model
                ->whereHas('machine', function ($query) use ($companyId) {
                    $query->where('company_id', '=', $companyId);
                })
                ->whereNull('resolved_at')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Throwable $e) {
            Log::error('AlertRepository::findOpenByCompany - Failed to retrieve open alerts by company', [
                'companyId' => $companyId,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve all critical alerts for a specific company.
     *
     * @param int $companyId The company ID.
     * @return Collection A collection of critical alerts.
     */
    public function findCriticalByCompany(int $companyId): Collection
    {
        try {
            return $this->model
                ->whereHas('machine', function ($query) use ($companyId) {
                    $query->where('company_id', '=', $companyId);
                })
                ->where('severity', '=', 'critical')
                ->whereNull('resolved_at')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Throwable $e) {
            Log::error('AlertRepository::findCriticalByCompany - Failed to retrieve critical alerts by company', [
                'companyId' => $companyId,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve all alerts associated with a specific machine.
     *
     * @param int $machineId The machine ID.
     * @return Collection A collection of alerts for the machine.
     */
    public function findByMachine(int $machineId): Collection
    {
        try {
            return $this->model
                ->where('machine_id', '=', $machineId)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Throwable $e) {
            Log::error('AlertRepository::findByMachine - Failed to retrieve alerts by machine', [
                'machineId' => $machineId,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Acknowledge an alert by setting the acknowledged timestamp and user.
     *
     * @param int $alertId The alert ID.
     * @param int $userId  The user ID who is acknowledging the alert.
     * @return bool True if the alert was acknowledged, false otherwise.
     */
    public function acknowledge(int $alertId, int $userId): bool
    {
        try {
            $alert = $this->model->find($alertId);

            if (!$alert) {
                Log::warning('AlertRepository::acknowledge - Alert not found', ['alertId' => $alertId]);
                return false;
            }

            $alert->acknowledged_at   = now();
            $alert->acknowledged_by   = $userId;

            return $alert->save();
        } catch (\Throwable $e) {
            Log::error('AlertRepository::acknowledge - Failed to acknowledge alert', [
                'alertId' => $alertId,
                'userId'  => $userId,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Resolve an alert by setting the resolved timestamp and user.
     *
     * @param int $alertId The alert ID.
     * @param int $userId  The user ID who is resolving the alert.
     * @return bool True if the alert was resolved, false otherwise.
     */
    public function resolve(int $alertId, int $userId): bool
    {
        try {
            $alert = $this->model->find($alertId);

            if (!$alert) {
                Log::warning('AlertRepository::resolve - Alert not found', ['alertId' => $alertId]);
                return false;
            }

            $alert->resolved_at   = now();
            $alert->resolved_by   = $userId;

            return $alert->save();
        } catch (\Throwable $e) {
            Log::error('AlertRepository::resolve - Failed to resolve alert', [
                'alertId' => $alertId,
                'userId'  => $userId,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Retrieve alert trend data for a company over a specified number of days.
     *
     * @param int $companyId The company ID.
     * @param int $days      The number of days to look back.
     * @return Collection A collection of aggregated alert counts grouped by date.
     */
    public function getAlertTrend(int $companyId, int $days = 7): Collection
    {
        try {
            $from = now()->subDays($days);

            return $this->model
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total, severity')
                ->whereHas('machine', function ($query) use ($companyId) {
                    $query->where('company_id', '=', $companyId);
                })
                ->where('created_at', '>=', $from)
                ->groupByRaw('DATE(created_at), severity')
                ->orderBy('date', 'asc')
                ->get();
        } catch (\Throwable $e) {
            Log::error('AlertRepository::getAlertTrend - Failed to retrieve alert trend', [
                'companyId' => $companyId,
                'days'      => $days,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }
}
