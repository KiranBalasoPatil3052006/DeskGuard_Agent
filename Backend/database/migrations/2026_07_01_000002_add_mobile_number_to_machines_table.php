 after<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add employee_mobile_number to machines table.
 *
 * Stores the mobile number of the employee using this machine.
 * Displayed on dashboards and included in alert email notifications.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            if (!Schema::hasColumn('machines', 'employee_mobile_number')) {
                $table->string('employee_mobile_number', 20)->nullable()->after('device_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn('employee_mobile_number');
        });
    }
};
