<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class UserRepository
 *
 * Repository for User-related database operations.
 * Extends BaseRepository with user-specific query methods.
 *
 * @package App\Repositories
 */
class UserRepository extends BaseRepository
{
    /**
     * UserRepository constructor.
     *
     * @param User $user The User model instance.
     */
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    /**
     * Find a user by their email address.
     *
     * @param string $email The email address to search for.
     * @return User|null The user instance if found, null otherwise.
     */
    public function findByEmail(string $email): ?User
    {
        try {
            return $this->model->where('email', '=', $email)->first();
        } catch (\Throwable $e) {
            Log::error('UserRepository::findByEmail - Failed to find user by email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Retrieve all users belonging to a specific company.
     *
     * @param int $companyId The company ID.
     * @return Collection A collection of users for the given company.
     */
    public function findByCompany(int $companyId): Collection
    {
        try {
            return $this->model->where('company_id', '=', $companyId)->get();
        } catch (\Throwable $e) {
            Log::error('UserRepository::findByCompany - Failed to find users by company', [
                'companyId' => $companyId,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve active users belonging to a specific company.
     *
     * @param int $companyId The company ID.
     * @return Collection A collection of active users for the given company.
     */
    public function findActiveByCompany(int $companyId): Collection
    {
        try {
            return $this->model
                ->where('company_id', '=', $companyId)
                ->where('is_active', '=', true)
                ->get();
        } catch (\Throwable $e) {
            Log::error('UserRepository::findActiveByCompany - Failed to find active users by company', [
                'companyId' => $companyId,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve users by their role within a specific company.
     *
     * @param string $role      The user role to filter by.
     * @param int    $companyId The company ID.
     * @return Collection A collection of matching users.
     */
    public function findUsersByRole(string $role, int $companyId): Collection
    {
        try {
            return $this->model
                ->where('company_id', '=', $companyId)
                ->role($role)
                ->get();
        } catch (\Throwable $e) {
            Log::error('UserRepository::findUsersByRole - Failed to find users by role', [
                'role'      => $role,
                'companyId' => $companyId,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }
}
