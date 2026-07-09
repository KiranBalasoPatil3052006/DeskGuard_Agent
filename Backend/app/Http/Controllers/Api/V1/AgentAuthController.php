<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgentAuthController extends Controller
{
    use ApiResponseTrait;

    private OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function requestOtp(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'mobile_number' => 'required|string|max:20',
            ]);

            $result = $this->otpService->generate($validated['mobile_number']);

            return $this->successResponse($result, 'OTP sent successfully.');
        } catch (Exception $e) {
            Log::error('AgentAuthController::requestOtp failed', [
                'mobile' => $request->input('mobile_number'),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to send OTP.', [], 500);
        }
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'mobile_number' => 'required|string|max:20',
                'otp' => 'required|string|size:6',
                'machine_uid' => 'required|string|max:255',
            ]);

            $otpRecord = $this->otpService->verify(
                $validated['mobile_number'],
                $validated['otp']
            );

            if (!$otpRecord) {
                return $this->errorResponse('Invalid or expired OTP.', [], 422);
            }

            $user = $this->otpService->findOrCreateUser($validated['mobile_number']);
            $token = $this->otpService->issueToken($user);

            return $this->successResponse([
                'token' => $token,
                'user' => $user,
            ], 'OTP verified successfully.');
        } catch (Exception $e) {
            Log::error('AgentAuthController::verifyOtp failed', [
                'mobile' => $request->input('mobile_number'),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('OTP verification failed.', [], 500);
        }
    }
}
