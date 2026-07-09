<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CompanyService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CompanyController
 *
 * Manages company CRUD operations and company head assignments.
 * Delegates all business logic to CompanyService.
 *
 * @package App\Http\Controllers\Api\V1
 */
class CompanyController extends Controller
{
    use ApiResponseTrait;

    private CompanyService $companyService;

    /**
     * CompanyController constructor.
     *
     * @param CompanyService $companyService
     */
    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * List all companies.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $companies = $this->companyService->getAllCompanies();

            return $this->successResponse($companies, 'Companies retrieved successfully.');
        } catch (Exception $e) {
            Log::error('CompanyController::index - Failed to retrieve companies', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve companies.', [], 500);
        }
    }

    /**
     * Create a new company.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'    => 'required|string|max:255',
                'email'   => 'nullable|email|max:255',
                'phone'   => 'nullable|string|max:50',
                'address' => 'nullable|string|max:1000',
                'website' => 'nullable|url|max:255',
            ]);

            $company = $this->companyService->createCompany($validated);

            return $this->createdResponse($company, 'Company created successfully.');
        } catch (Exception $e) {
            Log::error('CompanyController::store - Failed to create company', [
                'data'  => $request->all(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to create company.', [], 500);
        }
    }

    /**
     * Get company details by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $company = $this->companyService->getCompany($id);

            return $this->successResponse($company, 'Company retrieved successfully.');
        } catch (Exception $e) {
            Log::error('CompanyController::show - Failed to retrieve company', [
                'company_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Company not found.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 404
            );
        }
    }

    /**
     * Update an existing company.
     *
     * @param Request $request
     * @param int     $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'      => 'sometimes|string|max:255',
                'email'     => 'nullable|email|max:255',
                'phone'     => 'nullable|string|max:50',
                'address'   => 'nullable|string|max:1000',
                'website'   => 'nullable|url|max:255',
                'is_active' => 'sometimes|boolean',
            ]);

            $company = $this->companyService->updateCompany($id, $validated);

            return $this->successResponse($company, 'Company updated successfully.');
        } catch (Exception $e) {
            Log::error('CompanyController::update - Failed to update company', [
                'company_id' => $id,
                'data'       => $request->all(),
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to update company.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Deactivate (soft-delete) a company.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->companyService->deleteCompany($id);

            return $this->noContentResponse('Company deactivated successfully.');
        } catch (Exception $e) {
            Log::error('CompanyController::destroy - Failed to deactivate company', [
                'company_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to deactivate company.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Assign a user as the company head.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function assignHead(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_id' => 'required|integer|exists:companies,id',
                'user_id'    => 'required|integer|exists:users,id',
            ]);

            $this->companyService->assignCompanyHead(
                (int) $validated['company_id'],
                (int) $validated['user_id']
            );

            return $this->successResponse(null, 'Company head assigned successfully.');
        } catch (Exception $e) {
            Log::error('CompanyController::assignHead - Failed to assign company head', [
                'data'  => $request->all(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to assign company head.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }
}
