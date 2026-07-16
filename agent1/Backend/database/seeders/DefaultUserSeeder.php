<?php

namespace Database\Seeders;

use App\Constants\RoleConstants;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

/**
 * Class DefaultUserSeeder
 *
 * Creates the default user accounts for the DeskGuard demo environment:
 *
 * - Super Admin: admin@deskguard.com
 * - Company Head: head@deskguard.com
 * - Sub Head: subhead@deskguard.com
 * - Employee: employee@deskguard.com
 *
 * All users are assigned to the default company and given their
 * respective roles. Passwords are hashed.
 *
 * Idempotent: users are created only if they do not already exist.
 */
class DefaultUserSeeder extends Seeder
{
    /**
     * The default password used for all demo accounts.
     *
     * @var string
     */
    private const string DEFAULT_PASSWORD = 'password';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command?->info('Creating default users...');

        $company = Company::where('name', 'DeskGuard Demo Company')->first();

        if (!$company) {
            $this->command?->warn('Default company not found. Skipping user creation.');
            Log::warning('DefaultUserSeeder - Default company not found.');
            return;
        }

        $users = [
            [
                'name' => 'Kiran Patil',
                'email' => 'kiranbalasopatil33@gmail.com',
                'password' => 'kiranpatil33',
                'role' => RoleConstants::SUPER_ADMIN,
            ],
            [
                'name' => 'Super Admin',
                'email' => 'admin@deskguard.com',
                'password' => self::DEFAULT_PASSWORD,
                'role' => RoleConstants::SUPER_ADMIN,
            ],
            [
                'name' => 'Company Head',
                'email' => 'head@deskguard.com',
                'password' => self::DEFAULT_PASSWORD,
                'role' => RoleConstants::COMPANY_HEAD,
            ],
            [
                'name' => 'Sub Head',
                'email' => 'subhead@deskguard.com',
                'password' => self::DEFAULT_PASSWORD,
                'role' => RoleConstants::SUB_HEAD,
            ],
            [
                'name' => 'Employee',
                'email' => 'employee@deskguard.com',
                'password' => self::DEFAULT_PASSWORD,
                'role' => RoleConstants::EMPLOYEE,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'company_id' => $company->id,
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'is_active' => true,
                ]
            );

            $role = Role::findByName($userData['role'], 'web');

            if ($role && !$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }

            Log::info('Default user created', [
                'email' => $userData['email'],
                'role' => $userData['role'],
                'user_id' => $user->id,
            ]);
        }

        Log::info('DefaultUserSeeder completed', [
            'users_count' => count($users),
        ]);

        $this->command?->info('Default users created successfully.');
    }
}
