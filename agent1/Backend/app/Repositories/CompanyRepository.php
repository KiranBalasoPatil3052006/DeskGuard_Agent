<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class CompanyRepository
 *
 * Repository for Company-related database operations.
 * Extends BaseRepository with company-specific query methods.
 *
 * @package App\Repositories
 */
class CompanyRepository extends BaseRepository
{
    /**
     * CompanyRepository constructor.
     *
     * @param Company $company The Company model instance.
     */
    public function __construct(Company $company)
    {
        parent::__construct($company);
    }

    /**
     * Retrieve all active companies.
     *
     * @return Collection A collection of active companies.
     */
    public function findActive(): Collection
    {
        try {
            return $this->model->where('is_active', '=', true)->get();
        } catch (\Throwable $e) {
            Log::error('CompanyRepository::findActive - Failed to retrieve active companies', [
                'error' => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Find a company by its email address.
     *
     * @param string $email The email address to search for.
     * @return Company|null The company instance if found, null otherwise.
     */
    public function findByEmail(string $email): ?Company
    {
        try {
            return $this->model->where('email', '=', $email)->first();
        } catch (\Throwable $e) {
            Log::error('CompanyRepository::findByEmail - Failed to find company by email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
