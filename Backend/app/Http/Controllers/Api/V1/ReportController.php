<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ReportController
 *
 * Manages report generation, listing, download, and deletion.
 * Delegates all business logic to ReportService.
 *
 * @package App\Http\Controllers\Api\V1
 */
class ReportController extends Controller
{
    use ApiResponseTrait;

    private ReportService $reportService;

    /**
     * ReportController constructor.
     *
     * @param ReportService $reportService
     */
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * List all reports for the authenticated user's company.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $reports = $this->reportService->getCompanyReports($companyId);

            return $this->successResponse($reports, 'Reports retrieved successfully.');
        } catch (Exception $e) {
            Log::error('ReportController::index - Failed to retrieve reports', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve reports.', [], 500);
        }
    }

    /**
     * Generate a new report.
     *
     * Validates type and format, then dispatches generation to the service.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type'    => 'required|string|in:health,inventory,security,custom',
                'format'  => 'required|string|in:pdf,excel,csv',
                'filters' => 'nullable|array',
            ]);

            $user = Auth::user();

            $report = $this->reportService->generateReport(
                (int) $user->company_id,
                (int) $user->id,
                $validated['type'],
                $validated['format'],
                $validated['filters'] ?? null
            );

            return $this->createdResponse($report, 'Report generated successfully.');
        } catch (Exception $e) {
            Log::error('ReportController::generate - Failed to generate report', [
                'data'  => $request->all(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to generate report.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Download a report file by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function download(int $id): JsonResponse
    {
        try {
            $response = $this->reportService->downloadReport($id);

            return $response;
        } catch (Exception $e) {
            Log::error('ReportController::download - Failed to download report', [
                'report_id' => $id,
                'error'     => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to download report.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Delete a report by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->reportService->deleteReport($id);

            return $this->noContentResponse('Report deleted successfully.');
        } catch (Exception $e) {
            Log::error('ReportController::destroy - Failed to delete report', [
                'report_id' => $id,
                'error'     => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to delete report.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }
}
