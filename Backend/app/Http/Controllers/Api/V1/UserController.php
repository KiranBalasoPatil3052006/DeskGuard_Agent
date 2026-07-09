<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * UserController
 *
 * Manages user CRUD operations and role assignments.
 * Delegates all business logic to UserService.
 *
 * @package App\Http\Controllers\Api\V1
 */
class UserController extends Controller
{
    use ApiResponseTrait;

    private UserService $userService;

    /**
     * UserController constructor.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * List users, filtered by company for non-admin users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'role' => 'nullable|string|exists:roles,name',
            ]);

            $users = $this->userService->getUsersByCompany(
                (int) $user->company_id,
                $validated['role'] ?? null
            );

            return $this->successResponse($users, 'Users retrieved successfully.');
        } catch (Exception $e) {
            Log::error('UserController::index - Failed to retrieve users', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve users.', [], 500);
        }
    }

    /**
     * Create a new user with role assignment.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_id'          => 'nullable|integer|exists:companies,id',
                'name'                => 'required|string|max:255',
                'email'               => 'required|email|max:255|unique:users,email',
                'password'            => 'required|string|min:8',
                'phone'               => 'nullable|string|max:50',
                'role'                => 'required|string|exists:roles,name',
                'must_change_password' => 'boolean',
            ]);

            $role = $validated['role'];
            unset($validated['role']);

            $newUser = $this->userService->createUser($validated, $role);

            return $this->createdResponse($newUser, 'User created successfully.');
        } catch (Exception $e) {
            Log::error('UserController::store - Failed to create user', [
                'data'  => $request->all(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to create user.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Get user details by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUser($id);

            return $this->successResponse($user, 'User retrieved successfully.');
        } catch (Exception $e) {
            Log::error('UserController::show - Failed to retrieve user', [
                'user_id' => $id,
                'error'   => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'User not found.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 404
            );
        }
    }

    /**
     * Update an existing user.
     *
     * @param Request $request
     * @param int     $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'       => 'sometimes|string|max:255',
                'email'      => 'sometimes|email|max:255|unique:users,email,' . $id,
                'phone'      => 'nullable|string|max:50',
                'is_active'  => 'sometimes|boolean',
                'company_id' => 'sometimes|integer|exists:companies,id',
            ]);

            $updatedUser = $this->userService->updateUser($id, $validated);

            return $this->successResponse($updatedUser, 'User updated successfully.');
        } catch (Exception $e) {
            Log::error('UserController::update - Failed to update user', [
                'user_id' => $id,
                'data'    => $request->all(),
                'error'   => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to update user.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Soft-delete a user.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->deleteUser($id);

            return $this->noContentResponse('User deleted successfully.');
        } catch (Exception $e) {
            Log::error('UserController::destroy - Failed to delete user', [
                'user_id' => $id,
                'error'   => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to delete user.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Change the role assigned to a user.
     *
     * @param Request $request
     * @param int     $id
     * @return JsonResponse
     */
    public function assignRole(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role' => 'required|string|exists:roles,name',
            ]);

            $this->userService->assignRole($id, $validated['role']);

            return $this->successResponse(null, 'Role assigned successfully.');
        } catch (Exception $e) {
            Log::error('UserController::assignRole - Failed to assign role', [
                'user_id' => $id,
                'data'    => $request->all(),
                'error'   => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to assign role.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }
}
