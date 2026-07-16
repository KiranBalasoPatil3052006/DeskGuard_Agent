<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Class DatabaseSeeder
 *
 * The main seeder that orchestrates the initial seeding of the
 * DeskGuard application by calling the role, permission, company,
 * and user seeders in the correct order.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DefaultCompanySeeder::class,
            DefaultUserSeeder::class,
        ]);
    }
}
