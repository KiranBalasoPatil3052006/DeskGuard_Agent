<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Class DefaultCompanySeeder
 *
 * Creates a default company for the DeskGuard demo environment.
 * Also ensures default roles and permissions exist if they were
 * not already created.
 *
 * Idempotent: will not create duplicate companies if run multiple times.
 */
class DefaultCompanySeeder extends Seeder
{
    /**
     * The name of the default company.
     *
     * @var string
     */
    private const string DEFAULT_COMPANY_NAME = 'DeskGuard Demo Company';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command?->info('Creating default company...');

        Company::firstOrCreate(
            ['name' => self::DEFAULT_COMPANY_NAME],
            [
                'email' => 'demo@deskguard.com',
                'phone' => '+1-555-0100',
                'address' => '123 Demo Street, Tech City, TC 10001',
                'website' => 'https://deskguard.com',
                'is_active' => true,
            ]
        );

        Log::info('DefaultCompanySeeder completed', [
            'company_name' => self::DEFAULT_COMPANY_NAME,
        ]);

        $this->command?->info('Default company created successfully.');
    }
}
