<?php

namespace Database\Seeders;

use App\Constants\PermissionConstants;
use App\Constants\RoleConstants;
use App\Services\PermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Class RolePermissionSeeder
 *
 * Creates all application roles and permissions and assigns the correct
 * permissions to each role. Uses PermissionService for idempotent setup.
 * Safe to run multiple times without producing duplicate entries.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * The permission service instance.
     *
     * @var PermissionService
     */
    private PermissionService $permissionService;

    /**
     * Create a new seeder instance.
     *
     * @param  PermissionService  $permissionService
     * @return void
     */
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Run the database seeds.
     *
     * Creates all roles, creates all permissions from PermissionConstants,
     * and assigns the appropriate permissions to each role:
     *
     * - Super Admin: ALL permissions
     * - Company Head: create employees, create sub heads, delete employees,
     *   assign machines, view company systems, view reports, view alerts,
     *   manage company settings
     * - Sub Head: view assigned employees, view assigned machines,
     *   view reports, view alerts
     * - Employee: view own machine, view own alerts, view own reports
     *
     * @return void
     */
    public function run(): void
    {
        $this->command?->info('Setting up roles and permissions...');

        $this->permissionService->setupDefaultRolesAndPermissions();

        Log::info('RolePermissionSeeder completed successfully');

        $this->command?->info('Roles and permissions set up successfully.');
    }
}
