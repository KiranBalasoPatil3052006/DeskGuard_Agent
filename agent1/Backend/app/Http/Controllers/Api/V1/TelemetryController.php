<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\TelemetryPayloadDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\TelemetryRequest;
use App\Services\TelemetryService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TelemetryController extends Controller
{
    use ApiResponseTrait;

    private TelemetryService $telemetryService;

    public function __construct(TelemetryService $telemetryService)
    {
        $this->telemetryService = $telemetryService;
    }

    public function __invoke(TelemetryRequest $request): JsonResponse
    {
        try {
            $dto = new TelemetryPayloadDTO($request->validated());

            $this->telemetryService->processTelemetry($dto);

            return $this->successResponse(null, 'Telemetry data processed successfully.');
        } catch (Exception $e) {
            Log::error('TelemetryController - Failed to process telemetry', [
                'machineId' => $request->input('machineId'),
                'error'     => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to process telemetry data.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }
}
