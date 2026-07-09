<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * DashboardController
 *
 * Provides aggregated dashboard data for company heads and employees.
 * Returns summary cards and chart data for CPU, RAM, and alert trends.
 * Delegates all business logic to DashboardService.
 *
 * @package App\Http\Controllers\Api\V1
 */
class DashboardController extends Controller
{
    use ApiResponseTrait;

    private DashboardService $dashboardService;

    /**
     * DashboardController constructor.
     *
     * @param DashboardService $dashboardService
     */
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Company head dashboard with summary cards and chart data.
     *
     * @return JsonResponse
     */
    public function company(): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $dashboard = $this->dashboardService->getCompanyDashboard($companyId);

            return $this->successResponse($dashboard, 'Company dashboard retrieved successfully.');
        } catch (Exception $e) {
            Log::error('DashboardController::company - Failed to retrieve company dashboard', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve company dashboard.', [], 500);
        }
    }

    /**
     * Employee dashboard showing assigned machine status and recent alerts.
     *
     * @return JsonResponse
     */
    public function employee(): JsonResponse
    {
        try {
            $userId = (int) Auth::id();

            $dashboard = $this->dashboardService->getEmployeeDashboard($userId);

            return $this->successResponse($dashboard, 'Employee dashboard retrieved successfully.');
        } catch (Exception $e) {
            Log::error('DashboardController::employee - Failed to retrieve employee dashboard', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve employee dashboard.', [], 500);
        }
    }

    /**
     * CPU usage trend chart data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cpuTrend(Request $request): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $validated = $request->validate([
                'hours' => 'nullable|integer|min:1|max:168',
            ]);

            $hours = $validated['hours'] ?? 24;

            $data = $this->dashboardService->getCpuChartData($companyId, $hours);

            return $this->successResponse($data, 'CPU trend data retrieved successfully.');
        } catch (Exception $e) {
            Log::error('DashboardController::cpuTrend - Failed to retrieve CPU trend', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve CPU trend data.', [], 500);
        }
    }

    /**
     * RAM usage trend chart data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ramTrend(Request $request): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $validated = $request->validate([
                'hours' => 'nullable|integer|min:1|max:168',
            ]);

            $hours = $validated['hours'] ?? 24;

            $data = $this->dashboardService->getRamChartData($companyId, $hours);

            return $this->successResponse($data, 'RAM trend data retrieved successfully.');
        } catch (Exception $e) {
            Log::error('DashboardController::ramTrend - Failed to retrieve RAM trend', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve RAM trend data.', [], 500);
        }
    }

    /**
     * Alert trend chart data (daily alert counts grouped by severity).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function alertTrend(Request $request): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $validated = $request->validate([
                'days' => 'nullable|integer|min:1|max:90',
            ]);

            $days = $validated['days'] ?? 7;

            $data = $this->dashboardService->getAlertChartData($companyId, $days);

            return $this->successResponse($data, 'Alert trend data retrieved successfully.');
        } catch (Exception $e) {
            Log::error('DashboardController::alertTrend - Failed to retrieve alert trend', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve alert trend data.', [], 500);
        }
    }
}
