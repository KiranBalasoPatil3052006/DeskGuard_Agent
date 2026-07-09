<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventType;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Class UserService
 *
 * Handles CRUD operations for users and role management.
 * All mutations are logged to the audit log for traceability.
 *
 * @package App\Services
 */
class UserService
{
    /**
     * The audit log service for recording user events.
     *
     * @var AuditLogService
     */
    private AuditLogService $auditLogService;

    /**
     * UserService constructor.
     *
     * @param AuditLogService $auditLogService
     */
    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Create a new user and assign the given role.
     *
     * @param  array   $data  ['company_id' => int, 'name' => string, 'email' => string, 'password' => string, 'phone' => string|null]
     * @param  string  $role  The Spatie role name to assign.
     * @return User
     */
    public function createUser(array $data, string $role): User
    {
        try {
            $user = User::create([
                'company_id'          => $data['company_id'] ?? null,
                'name'                => $data['name'],
                'email'               => $data['email'],
                'password'            => Hash::make($data['password']),
                'phone'               => $data['phone'] ?? null,
                'is_active'           => true,
                'must_change_password' => $data['must_change_password'] ?? true,
            ]);

            $user->assignRole($role);

            $this->auditLogService->log(
                EventType::Create->value,
                'User created: ' . $user->email . ' with role: ' . $role,
                null,
                $user->toArray(),
                $user,
                null
            );

            Log::info('User created successfully', [
                'user_id'    => $user->id,
                'company_id' => $user->company_id,
                'role'       => $role,
            ]);

            return $user;
        } catch (Exception $e) {
            Log::error('UserService::createUser - Failed to create user', [
                'data'  => $data,
                'role'  => $role,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing user.
     *
     * @param  int    $id
     * @param  array  $data
     * @return User
     */
    public function updateUser(int $id, array $data): User
    {
        try {
            $user = User::findOrFail($id);
            $oldValues = $user->toArray();

            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['email'])) {
                $updateData['email'] = $data['email'];
            }
            if (isset($data['phone'])) {
                $updateData['phone'] = $data['phone'];
            }
            if (isset($data['is_active'])) {
                $updateData['is_active'] = $data['is_active'];
            }
            if (isset($data['company_id'])) {
                $updateData['company_id'] = $data['company_id'];
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            $user->refresh();

            $this->auditLogService->log(
                EventType::Update->value,
                'User updated: ' . $user->email,
                $oldValues,
                $user->toArray(),
                $user,
                null
            );

            Log::info('User updated successfully', [
                'user_id'    => $user->id,
                'company_id' => $user->company_id,
            ]);

            return $user;
        } catch (Exception $e) {
            Log::error('UserService::updateUser - Failed to update user', [
                'user_id' => $id,
                'data'    => $data,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete (soft-delete) a user.
     *
     * @param  int   $id
     * @return bool
     */
    public function deleteUser(int $id): bool
    {
        try {
            $user = User::findOrFail($id);
            $oldValues = $user->toArray();

            $user->delete();

            $this->auditLogService->log(
                EventType::Delete->value,
                'User deleted: ' . $user->email,
                $oldValues,
                null,
                $user,
                null
            );

            Log::info('User deleted successfully', [
                'user_id' => $user->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('UserService::deleteUser - Failed to delete user', [
                'user_id' => $id,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve a user by ID.
     *
     * @param  int   $id
     * @return User
     */
    public function getUser(int $id): User
    {
        try {
            return User::with(['company', 'roles'])->findOrFail($id);
        } catch (Exception $e) {
            Log::error('UserService::getUser - Failed to find user', [
                'user_id' => $id,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve users belonging to a company, optionally filtered by role.
     *
     * @param  int         $companyId
     * @param  string|null $role
     * @return Collection<int, User>
     */
    public function getUsersByCompany(int $companyId, ?string $role = null): Collection
    {
        try {
            $query = User::where('company_id', $companyId);

            if ($role !== null) {
                $query->role($role);
            }

            return $query->with(['company', 'roles'])->get();
        } catch (Exception $e) {
            Log::error('UserService::getUsersByCompany - Failed to retrieve users by company', [
                'company_id' => $companyId,
                'role'       => $role,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Assign a Spatie role to a user.
     *
     * @param  int     $userId
     * @param  string  $role
     * @return void
     */
    public function assignRole(int $userId, string $role): void
    {
        try {
            $user = User::findOrFail($userId);

            $user->syncRoles([$role]);

            $this->auditLogService->log(
                EventType::Update->value,
                'Role assigned to user: ' . $user->email . ' -> ' . $role,
                null,
                ['role' => $role],
                $user,
                null
            );

            Log::info('Role assigned to user', [
                'user_id' => $user->id,
                'role'    => $role,
            ]);
        } catch (Exception $e) {
            Log::error('UserService::assignRole - Failed to assign role', [
                'user_id' => $userId,
                'role'    => $role,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Change a user's password.
     *
     * Hashes the new password, saves it, and sets must_change_password to false.
     *
     * @param  int     $userId
     * @param  string  $newPassword
     * @return void
     */
    public function changePassword(int $userId, string $newPassword): void
    {
        try {
            $user = User::findOrFail($userId);

            $user->update([
                'password'            => Hash::make($newPassword),
                'must_change_password' => false,
            ]);

            $this->auditLogService->log(
                EventType::Update->value,
                'Password changed for user: ' . $user->email,
                null,
                null,
                $user,
                null
            );

            Log::info('Password changed for user', [
                'user_id' => $user->id,
            ]);
        } catch (Exception $e) {
            Log::error('UserService::changePassword - Failed to change password', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update the last login timestamp for a user.
     *
     * @param  int  $userId
     * @return void
     */
    public function updateLastLogin(int $userId): void
    {
        try {
            User::where('id', $userId)->update(['last_login_at' => now()]);

            Log::info('Last login updated for user', [
                'user_id' => $userId,
            ]);
        } catch (Exception $e) {
            Log::error('UserService::updateLastLogin - Failed to update last login', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
