<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\RoleConstants;
use App\Enums\EventType;
use App\Models\Company;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class CompanyService
 *
 * Handles CRUD operations for companies.
 * All mutations are logged to the audit log for traceability.
 *
 * @package App\Services
 */
class CompanyService
{
    /**
     * The audit log service for recording company events.
     *
     * @var AuditLogService
     */
    private AuditLogService $auditLogService;

    /**
     * CompanyService constructor.
     *
     * @param AuditLogService $auditLogService
     */
    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Create a new company.
     *
     * @param  array  $data  ['name' => string, 'email' => string|null, 'phone' => string|null, 'address' => string|null, 'website' => string|null]
     * @return Company
     */
    public function createCompany(array $data): Company
    {
        try {
            $company = Company::create([
                'name'    => $data['name'],
                'email'   => $data['email'] ?? null,
                'phone'   => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'website' => $data['website'] ?? null,
                'is_active' => true,
            ]);

            $this->auditLogService->log(
                EventType::Create->value,
                'Company created: ' . $company->name,
                null,
                $company->toArray(),
                null,
                null
            );

            Log::info('Company created successfully', [
                'company_id' => $company->id,
                'name'       => $company->name,
            ]);

            return $company;
        } catch (Exception $e) {
            Log::error('CompanyService::createCompany - Failed to create company', [
                'data'  => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing company.
     *
     * @param  int    $id
     * @param  array  $data
     * @return Company
     */
    public function updateCompany(int $id, array $data): Company
    {
        try {
            $company = Company::findOrFail($id);
            $oldValues = $company->toArray();

            $company->update([
                'name'    => $data['name'] ?? $company->name,
                'email'   => $data['email'] ?? $company->email,
                'phone'   => $data['phone'] ?? $company->phone,
                'address' => $data['address'] ?? $company->address,
                'website' => $data['website'] ?? $company->website,
                'is_active' => $data['is_active'] ?? $company->is_active,
            ]);

            $company->refresh();

            $this->auditLogService->log(
                EventType::Update->value,
                'Company updated: ' . $company->name,
                $oldValues,
                $company->toArray(),
                null,
                null
            );

            Log::info('Company updated successfully', [
                'company_id' => $company->id,
                'name'       => $company->name,
            ]);

            return $company;
        } catch (Exception $e) {
            Log::error('CompanyService::updateCompany - Failed to update company', [
                'company_id' => $id,
                'data'       => $data,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Soft-delete (deactivate) a company.
     *
     * @param  int   $id
     * @return bool
     */
    public function deleteCompany(int $id): bool
    {
        try {
            $company = Company::findOrFail($id);
            $oldValues = $company->toArray();

            $company->update(['is_active' => false]);

            $this->auditLogService->log(
                EventType::Delete->value,
                'Company deactivated: ' . $company->name,
                $oldValues,
                $company->toArray(),
                null,
                null
            );

            Log::info('Company deactivated successfully', [
                'company_id' => $company->id,
                'name'       => $company->name,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('CompanyService::deleteCompany - Failed to deactivate company', [
                'company_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve a company by its ID.
     *
     * @param  int      $id
     * @return Company
     */
    public function getCompany(int $id): Company
    {
        try {
            return Company::findOrFail($id);
        } catch (Exception $e) {
            Log::error('CompanyService::getCompany - Failed to find company', [
                'company_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve all companies.
     *
     * @return Collection<int, Company>
     */
    public function getAllCompanies(): Collection
    {
        try {
            return Company::all();
        } catch (Exception $e) {
            Log::error('CompanyService::getAllCompanies - Failed to retrieve companies', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Assign a user as the Company Head for the given company.
     *
     * @param  int  $companyId
     * @param  int  $userId
     * @return void
     */
    public function assignCompanyHead(int $companyId, int $userId): void
    {
        try {
            DB::transaction(function () use ($companyId, $userId) {
                $company = Company::findOrFail($companyId);
                $user = User::findOrFail($userId);

                $user->company_id = $company->id;
                $user->save();

                $user->syncRoles([RoleConstants::COMPANY_HEAD]);

                $this->auditLogService->log(
                    EventType::Update->value,
                    'User assigned as Company Head: ' . $user->email . ' for company: ' . $company->name,
                    null,
                    ['company_id' => $company->id, 'user_id' => $user->id, 'role' => RoleConstants::COMPANY_HEAD],
                    $user,
                    null
                );

                Log::info('Company Head assigned successfully', [
                    'company_id' => $company->id,
                    'user_id'    => $user->id,
                ]);
            });
        } catch (Exception $e) {
            Log::error('CompanyService::assignCompanyHead - Failed to assign company head', [
                'company_id' => $companyId,
                'user_id'    => $userId,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
