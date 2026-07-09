<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * AuditLogController
 *
 * Provides read-only access to audit logs scoped to the authenticated
 * user's company. Supports filtering by event type and date range.
 * Delegates all business logic to AuditLogService.
 *
 * @package App\Http\Controllers\Api\V1
 */
class AuditLogController extends Controller
{
    use ApiResponseTrait;

    private AuditLogService $auditLogService;

    /**
     * AuditLogController constructor.
     *
     * @param AuditLogService $auditLogService
     */
    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * List audit logs scoped to the authenticated user's company.
     *
     * Supports optional filtering by event_type and date range (from, to).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $validated = $request->validate([
                'event_type' => 'nullable|string',
                'from'       => 'nullable|date',
                'to'         => 'nullable|date|after_or_equal:from',
            ]);

            $logs = $this->auditLogService->getLogs(
                $companyId,
                $validated['event_type'] ?? null,
                $validated['from'] ?? null,
                $validated['to'] ?? null
            );

            return $this->successResponse($logs, 'Audit logs retrieved successfully.');
        } catch (Exception $e) {
            Log::error('AuditLogController::index - Failed to retrieve audit logs', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve audit logs.', [], 500);
        }
    }
}
