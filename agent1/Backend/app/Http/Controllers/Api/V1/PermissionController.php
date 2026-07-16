<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PermissionService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PermissionController
 *
 * Manages roles, permissions, and their assignments.
 * Delegates all business logic to PermissionService.
 *
 * @package App\Http\Controllers\Api\V1
 */
class PermissionController extends Controller
{
    use ApiResponseTrait;

    private PermissionService $permissionService;

    /**
     * PermissionController constructor.
     *
     * @param PermissionService $permissionService
     */
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * List all available roles.
     *
     * @return JsonResponse
     */
    public function roles(): JsonResponse
    {
        try {
            $roles = $this->permissionService->getAllRoles();

            return $this->successResponse($roles, 'Roles retrieved successfully.');
        } catch (Exception $e) {
            Log::error('PermissionController::roles - Failed to retrieve roles', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve roles.', [], 500);
        }
    }

    /**
     * List all available permissions.
     *
     * @return JsonResponse
     */
    public function permissions(): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getAllPermissions();

            return $this->successResponse($permissions, 'Permissions retrieved successfully.');
        } catch (Exception $e) {
            Log::error('PermissionController::permissions - Failed to retrieve permissions', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve permissions.', [], 500);
        }
    }

    /**
     * Assign a permission to a role.
     *
     * @param Request $request
     * @param int     $roleId
     * @return JsonResponse
     */
    public function assignPermission(Request $request, int $roleId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'permission' => 'required|string|exists:permissions,name',
            ]);

            $this->permissionService->assignPermissionToRoleById($roleId, $validated['permission']);

            return $this->successResponse(null, 'Permission assigned to role successfully.');
        } catch (Exception $e) {
            Log::error('PermissionController::assignPermission - Failed to assign permission', [
                'role_id'    => $roleId,
                'data'       => $request->all(),
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to assign permission.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Get all permissions for a specific user.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function userPermissions(int $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $permissions = $this->permissionService->getUserPermissions($user);

            return $this->successResponse($permissions, 'User permissions retrieved successfully.');
        } catch (Exception $e) {
            Log::error('PermissionController::userPermissions - Failed to retrieve user permissions', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to retrieve user permissions.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }
}
