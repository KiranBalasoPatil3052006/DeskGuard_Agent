<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Services\AlertService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * AlertController
 *
 * Manages alerts, alert acknowledgement, resolution, and alert rule configuration.
 * Delegates all business logic to AlertService.
 *
 * @package App\Http\Controllers\Api\V1
 */
class AlertController extends Controller
{
    use ApiResponseTrait;

    private AlertService $alertService;

    /**
     * AlertController constructor.
     *
     * @param AlertService $alertService
     */
    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * List alerts scoped to the authenticated user's company, filterable by severity and status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $validated = $request->validate([
                'severity' => 'nullable|string|in:info,warning,critical',
                'status'   => 'nullable|string|in:open,acknowledged,resolved',
            ]);

            $perPage = min((int) $request->input('per_page', 50), 100);

            $alerts = $this->alertService->getCompanyAlerts(
                $companyId,
                $validated['severity'] ?? null,
                $validated['status'] ?? null,
                $perPage
            );

            return $this->successResponse($alerts, 'Alerts retrieved successfully.');
        } catch (Exception $e) {
            Log::error('AlertController::index - Failed to retrieve alerts', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve alerts.', [], 500);
        }
    }

    /**
     * Get alert details by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $alert = Alert::select(['id', 'company_id', 'machine_id', 'title', 'description', 'severity', 'status', 'acknowledged_by', 'resolved_by', 'metadata', 'created_at', 'updated_at'])
                ->with(['machine:id,hostname,device_name,machine_uid', 'acknowledgedBy:id,name', 'resolvedBy:id,name'])
                ->where('company_id', $companyId)
                ->findOrFail($id);

            return $this->successResponse($alert, 'Alert retrieved successfully.');
        } catch (Exception $e) {
            Log::error('AlertController::show - Failed to retrieve alert', [
                'alert_id' => $id,
                'error'    => $e->getMessage(),
            ]);
            return $this->errorResponse(
                'Alert not found.',
                [],
                404
            );
        }
    }

    /**
     * Acknowledge an alert.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function acknowledge(int $id): JsonResponse
    {
        try {
            $userId = (int) Auth::id();

            $alert = $this->alertService->acknowledgeAlert($id, $userId);

            return $this->successResponse($alert, 'Alert acknowledged successfully.');
        } catch (Exception $e) {
            Log::error('AlertController::acknowledge - Failed to acknowledge alert', [
                'alert_id' => $id,
                'error'    => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to acknowledge alert.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Resolve an alert with an optional resolution note.
     *
     * @param Request $request
     * @param int     $id
     * @return JsonResponse
     */
    public function resolve(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'note' => 'nullable|string|max:1000',
            ]);

            $userId = (int) Auth::id();

            $alert = $this->alertService->resolveAlert(
                $id,
                $userId,
                $validated['note'] ?? null
            );

            return $this->successResponse($alert, 'Alert resolved successfully.');
        } catch (Exception $e) {
            Log::error('AlertController::resolve - Failed to resolve alert', [
                'alert_id' => $id,
                'data'     => $request->all(),
                'error'    => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to resolve alert.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Get critical alerts for the authenticated user's company.
     *
     * @return JsonResponse
     */
    public function critical(): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $alerts = $this->alertService->getCriticalAlerts($companyId);

            return $this->successResponse($alerts, 'Critical alerts retrieved successfully.');
        } catch (Exception $e) {
            Log::error('AlertController::critical - Failed to retrieve critical alerts', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve critical alerts.', [], 500);
        }
    }

    /**
     * List alert rules for the authenticated user's company.
     *
     * @return JsonResponse
     */
    public function rules(): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $alertRules = $this->alertService->getAlertRules($companyId);

            return $this->successResponse($alertRules, 'Alert rules retrieved successfully.');
        } catch (Exception $e) {
            Log::error('AlertController::rules - Failed to retrieve alert rules', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve alert rules.', [], 500);
        }
    }

    /**
     * Update an alert rule configuration.
     *
     * @param Request $request
     * @param int     $id
     * @return JsonResponse
     */
    public function updateRule(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'             => 'sometimes|string|max:255',
                'description'      => 'nullable|string|max:1000',
                'metric'           => 'sometimes|string|in:cpu_percentage,cpu_temperature,ram_percentage,disk_percentage,battery_percentage',
                'operator'         => 'sometimes|string|in:>,>=,<,<=,==,!=',
                'value'            => 'sometimes|string|max:50',
                'severity'         => 'sometimes|string|in:info,warning,critical',
                'duration_minutes' => 'nullable|integer|min:1',
                'is_enabled'       => 'sometimes|boolean',
            ]);

            $alertRule = $this->alertService->updateAlertRule($id, $validated);

            return $this->successResponse($alertRule, 'Alert rule updated successfully.');
        } catch (Exception $e) {
            Log::error('AlertController::updateRule - Failed to update alert rule', [
                'rule_id' => $id,
                'data'    => $request->all(),
                'error'   => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to update alert rule.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }
}
