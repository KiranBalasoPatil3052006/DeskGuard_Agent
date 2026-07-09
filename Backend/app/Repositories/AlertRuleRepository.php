<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AlertRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class AlertRuleRepository
 *
 * Repository for AlertRule-related database operations.
 * Extends BaseRepository with alert-rule-specific query methods.
 *
 * @package App\Repositories
 */
class AlertRuleRepository extends BaseRepository
{
    /**
     * AlertRuleRepository constructor.
     *
     * @param AlertRule $alertRule The AlertRule model instance.
     */
    public function __construct(AlertRule $alertRule)
    {
        parent::__construct($alertRule);
    }

    /**
     * Retrieve all enabled alert rules for a specific company.
     *
     * @param int $companyId The company ID.
     * @return Collection A collection of enabled alert rules.
     */
    public function findEnabledByCompany(int $companyId): Collection
    {
        try {
            return $this->model
                ->where('company_id', '=', $companyId)
                ->where('is_enabled', '=', true)
                ->get();
        } catch (\Throwable $e) {
            Log::error('AlertRuleRepository::findEnabledByCompany - Failed to retrieve enabled rules', [
                'companyId' => $companyId,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Find an alert rule by its metric name and company ID.
     *
     * @param string $metric    The metric name (e.g. cpu_percentage, ram_percentage).
     * @param int    $companyId The company ID.
     * @return AlertRule|null The alert rule instance if found, null otherwise.
     */
    public function findByMetric(string $metric, int $companyId): ?AlertRule
    {
        try {
            return $this->model
                ->where('metric', '=', $metric)
                ->where('company_id', '=', $companyId)
                ->first();
        } catch (\Throwable $e) {
            Log::error('AlertRuleRepository::findByMetric - Failed to find alert rule by metric', [
                'metric'    => $metric,
                'companyId' => $companyId,
                'error'     => $e->getMessage(),
            ]);
            return null;
        }
    }
}
