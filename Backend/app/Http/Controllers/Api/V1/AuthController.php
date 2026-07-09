<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * AuthController
 *
 * Handles user authentication, token management, and session lifecycle.
 * Delegates all business logic to AuthService.
 *
 * @package App\Http\Controllers\Api\V1
 */
class AuthController extends Controller
{
    use ApiResponseTrait;

    private AuthService $authService;

    /**
     * AuthController constructor.
     *
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Authenticate a user and return a token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            $result = $this->authService->login($validated);

            return $this->successResponse($result, 'Login successful.');
        } catch (Exception $e) {
            Log::error('AuthController::login - Login failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Login failed.',
                method_exists($e, 'getErrors') ? $e->getErrors() : [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 401
            );
        }
    }

    /**
     * Logout the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user) {
                $this->authService->logout($user);
            }

            return $this->successResponse(null, 'Logged out successfully.');
        } catch (Exception $e) {
            Log::error('AuthController::logout - Logout failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Logout failed.', [], 500);
        }
    }

    /**
     * Get the currently authenticated user's data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->getAuthenticatedUser();

            return $this->successResponse($user, 'Authenticated user retrieved.');
        } catch (Exception $e) {
            Log::error('AuthController::me - Failed to retrieve authenticated user', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Unable to retrieve authenticated user.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 401
            );
        }
    }

    /**
     * Refresh the authentication token for the current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->errorResponse('No authenticated user found.', [], 401);
            }

            $newToken = $this->authService->refreshToken($user);

            return $this->successResponse(['token' => $newToken], 'Token refreshed successfully.');
        } catch (Exception $e) {
            Log::error('AuthController::refreshToken - Failed to refresh token', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to refresh token.', [], 500);
        }
    }
}
