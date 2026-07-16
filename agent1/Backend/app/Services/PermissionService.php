<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\PermissionConstants;
use App\Constants\RoleConstants;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Class PermissionService
 *
 * Manages roles and permissions using the Spatie Permission package.
 * Provides methods for creating roles, permissions, and assigning
 * them to users. Includes idempotent setup of all default roles
 * and permissions used by the DeskGuard application.
 *
 * @package App\Services
 */
class PermissionService
{
    /**
     * PermissionService constructor.
     */
    public function __construct()
    {
    }

    /**
     * Create a new role.
     *
     * @param  string  $name
     * @return Role
     */
    public function createRole(string $name): Role
    {
        try {
            $role = Role::create(['name' => $name, 'guard_name' => 'web']);

            Log::info('Role created', [
                'role_name' => $name,
                'role_id'   => $role->id,
            ]);

            return $role;
        } catch (Exception $e) {
            Log::error('PermissionService::createRole - Failed to create role', [
                'name'  => $name,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a new permission.
     *
     * @param  string      $name
     * @return Permission
     */
    public function createPermission(string $name): Permission
    {
        try {
            $permission = Permission::create(['name' => $name, 'guard_name' => 'web']);

            Log::info('Permission created', [
                'permission_name' => $name,
                'permission_id'   => $permission->id,
            ]);

            return $permission;
        } catch (Exception $e) {
            Log::error('PermissionService::createPermission - Failed to create permission', [
                'name'  => $name,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Assign a permission to a role.
     *
     * @param  string  $permission  The permission name.
     * @param  string  $role        The role name.
     * @return void
     */
    public function assignPermissionToRole(string $permission, string $role): void
    {
        try {
            $roleModel = Role::findByName($role, 'web');
            $permissionModel = Permission::findByName($permission, 'web');

            $roleModel->givePermissionTo($permissionModel);

            Log::info('Permission assigned to role', [
                'permission' => $permission,
                'role'       => $role,
            ]);
        } catch (Exception $e) {
            Log::error('PermissionService::assignPermissionToRole - Failed to assign permission to role', [
                'permission' => $permission,
                'role'       => $role,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync the permissions for a role (replace all existing permissions).
     *
     * @param  string  $role         The role name.
     * @param  array   $permissions  Array of permission names.
     * @return void
     */
    public function syncRolePermissions(string $role, array $permissions): void
    {
        try {
            $roleModel = Role::findByName($role, 'web');

            $roleModel->syncPermissions($permissions);

            Log::info('Role permissions synced', [
                'role'        => $role,
                'permissions' => $permissions,
            ]);
        } catch (Exception $e) {
            Log::error('PermissionService::syncRolePermissions - Failed to sync role permissions', [
                'role'        => $role,
                'permissions' => $permissions,
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve all roles.
     *
     * @return Collection<int, Role>
     */
    public function getAllRoles(): Collection
    {
        try {
            return Role::all();
        } catch (Exception $e) {
            Log::error('PermissionService::getAllRoles - Failed to retrieve roles', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve all permissions.
     *
     * @return Collection<int, Permission>
     */
    public function getAllPermissions(): Collection
    {
        try {
            return Permission::all();
        } catch (Exception $e) {
            Log::error('PermissionService::getAllPermissions - Failed to retrieve permissions', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Assign a permission to a role by role ID and permission name.
     *
     * @param  int     $roleId
     * @param  string  $permissionName
     * @return void
     */
    public function assignPermissionToRoleById(int $roleId, string $permissionName): void
    {
        try {
            $role = Role::findOrFail($roleId);
            $permission = Permission::findByName($permissionName, 'web');
            $role->givePermissionTo($permission);

            Log::info('Permission assigned to role', [
                'permission' => $permissionName,
                'role_id'    => $roleId,
            ]);
        } catch (Exception $e) {
            Log::error('PermissionService::assignPermissionToRoleById - Failed to assign permission', [
                'role_id'    => $roleId,
                'permission' => $permissionName,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get all permissions for a given user.
     *
     * @param  User        $user
     * @return Collection<int, Permission>
     */
    public function getUserPermissions(User $user): Collection
    {
        try {
            return $user->getAllPermissions();
        } catch (Exception $e) {
            Log::error('PermissionService::getUserPermissions - Failed to get user permissions', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Idempotent setup of all default roles and permissions.
     *
     * Creates all permissions and roles, then assigns the correct
     * permissions to each role. Safe to call multiple times.
     *
     * @return void
     */
    public function setupDefaultRolesAndPermissions(): void
    {
        try {
            $allPermissions = [
                PermissionConstants::MANAGE_COMPANIES,
                PermissionConstants::MANAGE_LICENSES,
                PermissionConstants::MANAGE_ALL_USERS,
                PermissionConstants::VIEW_ALL_SYSTEMS,
                PermissionConstants::MANAGE_ALL_DATA,
                PermissionConstants::CREATE_EMPLOYEES,
                PermissionConstants::CREATE_SUB_HEADS,
                PermissionConstants::DELETE_EMPLOYEES,
                PermissionConstants::ASSIGN_MACHINES,
                PermissionConstants::VIEW_COMPANY_SYSTEMS,
                PermissionConstants::VIEW_REPORTS,
                PermissionConstants::VIEW_ALERTS,
                PermissionConstants::MANAGE_COMPANY_SETTINGS,
                PermissionConstants::VIEW_ASSIGNED_EMPLOYEES,
                PermissionConstants::VIEW_ASSIGNED_MACHINES,
                PermissionConstants::VIEW_OWN_MACHINE,
                PermissionConstants::VIEW_OWN_ALERTS,
                PermissionConstants::VIEW_OWN_REPORTS,
            ];

            foreach ($allPermissions as $permissionName) {
                Permission::firstOrCreate([
                    'name'       => $permissionName,
                    'guard_name' => 'web',
                ]);
            }

            $superAdminRole = Role::firstOrCreate([
                'name'       => RoleConstants::SUPER_ADMIN,
                'guard_name' => 'web',
            ]);
            $superAdminRole->syncPermissions(Permission::all());

            $companyHeadRole = Role::firstOrCreate([
                'name'       => RoleConstants::COMPANY_HEAD,
                'guard_name' => 'web',
            ]);
            $companyHeadRole->syncPermissions([
                PermissionConstants::CREATE_EMPLOYEES,
                PermissionConstants::CREATE_SUB_HEADS,
                PermissionConstants::DELETE_EMPLOYEES,
                PermissionConstants::ASSIGN_MACHINES,
                PermissionConstants::VIEW_COMPANY_SYSTEMS,
                PermissionConstants::VIEW_REPORTS,
                PermissionConstants::VIEW_ALERTS,
                PermissionConstants::MANAGE_COMPANY_SETTINGS,
            ]);

            $subHeadRole = Role::firstOrCreate([
                'name'       => RoleConstants::SUB_HEAD,
                'guard_name' => 'web',
            ]);
            $subHeadRole->syncPermissions([
                PermissionConstants::VIEW_ASSIGNED_EMPLOYEES,
                PermissionConstants::VIEW_ASSIGNED_MACHINES,
                PermissionConstants::VIEW_REPORTS,
                PermissionConstants::VIEW_ALERTS,
            ]);

            $employeeRole = Role::firstOrCreate([
                'name'       => RoleConstants::EMPLOYEE,
                'guard_name' => 'web',
            ]);
            $employeeRole->syncPermissions([
                PermissionConstants::VIEW_OWN_MACHINE,
                PermissionConstants::VIEW_OWN_ALERTS,
                PermissionConstants::VIEW_OWN_REPORTS,
            ]);

            Log::info('Default roles and permissions set up successfully', [
                'permissions_count' => count($allPermissions),
                'roles' => [
                    RoleConstants::SUPER_ADMIN,
                    RoleConstants::COMPANY_HEAD,
                    RoleConstants::SUB_HEAD,
                    RoleConstants::EMPLOYEE,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('PermissionService::setupDefaultRolesAndPermissions - Failed to set up roles and permissions', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
