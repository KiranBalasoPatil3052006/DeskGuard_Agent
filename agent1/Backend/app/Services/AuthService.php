<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventType;
use App\Exceptions\UnauthorizedActionException;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Class AuthService
 *
 * Handles user authentication, token management, and session lifecycle.
 * Every login attempt (success or failure) is recorded to the audit log.
 *
 * @package App\Services
 */
class AuthService
{
    /**
     * The audit log service for recording authentication events.
     *
     * @var AuditLogService
     */
    private AuditLogService $auditLogService;

    /**
     * AuthService constructor.
     *
     * @param AuditLogService $auditLogService
     */
    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Authenticate a user with the given credentials.
     *
     * Validates email and password, returns a Sanctum token and user data
     * on success. Throws UnauthorizedActionException on invalid credentials.
     *
     * @param  array  $credentials  ['email' => string, 'password' => string]
     * @return array  ['token' => string, 'user' => User]
     *
     * @throws UnauthorizedActionException
     */
    public function login(array $credentials): array
    {
        try {
            if (!isset($credentials['email'], $credentials['password'])) {
                throw new UnauthorizedActionException(
                    'Email and password are required.',
                    422
                );
            }

            $user = User::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                $this->auditLogService->log(
                    EventType::Login->value,
                    'Failed login attempt for email: ' . ($credentials['email'] ?? 'unknown'),
                    null,
                    null,
                    $user ?? null
                );

                throw new UnauthorizedActionException(
                    'The provided credentials are incorrect.',
                    401,
                    ['email' => $credentials['email'] ?? '']
                );
            }

            if (!$user->is_active) {
                $this->auditLogService->log(
                    EventType::Login->value,
                    'Login attempt for inactive account: ' . $user->email,
                    null,
                    null,
                    $user
                );

                throw new UnauthorizedActionException(
                    'Your account has been deactivated. Please contact your administrator.',
                    403,
                    ['user_id' => $user->id]
                );
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            $user->update(['last_login_at' => now()]);

            $user->load(['company', 'roles']);

            $this->auditLogService->log(
                EventType::Login->value,
                'User logged in successfully: ' . $user->email,
                null,
                null,
                $user
            );

            Log::info('User logged in successfully', [
                'user_id'    => $user->id,
                'company_id' => $user->company_id,
                'email'      => $user->email,
            ]);

            return [
                'token' => $token,
                'user'  => $user,
            ];
        } catch (UnauthorizedActionException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('AuthService::login - Unexpected error during login', [
                'email' => $credentials['email'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            throw new UnauthorizedActionException(
                'An unexpected error occurred during login. Please try again.',
                500
            );
        }
    }

    /**
     * Revoke the current Sanctum token for the given user.
     *
     * @param  User  $user
     * @return void
     */
    public function logout(User $user): void
    {
        try {
            $user->currentAccessToken()->delete();

            $this->auditLogService->log(
                EventType::Logout->value,
                'User logged out: ' . $user->email,
                null,
                null,
                $user
            );

            Log::info('User logged out successfully', [
                'user_id'    => $user->id,
                'company_id' => $user->company_id,
            ]);
        } catch (Exception $e) {
            Log::error('AuthService::logout - Failed to logout user', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve the currently authenticated user with company and roles loaded.
     *
     * @return User
     */
    public function getAuthenticatedUser(): User
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (!$user) {
                throw new UnauthorizedActionException(
                    'No authenticated user found.',
                    401
                );
            }

            $user->load(['company', 'roles']);

            return $user;
        } catch (UnauthorizedActionException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('AuthService::getAuthenticatedUser - Failed to retrieve authenticated user', [
                'error' => $e->getMessage(),
            ]);
            throw new UnauthorizedActionException(
                'Unable to retrieve authenticated user.',
                500
            );
        }
    }

    /**
     * Issue a new Sanctum token for the given user and revoke the old one.
     *
     * @param  User  $user
     * @return string  The new plain-text token.
     */
    public function refreshToken(User $user): string
    {
        try {
            $user->currentAccessToken()->delete();

            $newToken = $user->createToken('auth-token')->plainTextToken;

            $this->auditLogService->log(
                EventType::Update->value,
                'Token refreshed for user: ' . $user->email,
                null,
                null,
                $user
            );

            Log::info('Token refreshed for user', [
                'user_id'    => $user->id,
                'company_id' => $user->company_id,
            ]);

            return $newToken;
        } catch (Exception $e) {
            Log::error('AuthService::refreshToken - Failed to refresh token', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
