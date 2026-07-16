<?php

/**
 * Migration: Create configuration_baselines table.
 *
 * Stores the approved configuration state for each machine,
 * covering OS settings, startup programs, services, and other
 * system configuration items. Enables detection of unauthorized
 * configuration changes by comparing current state against baseline.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuration_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('setting_key')->comment('e.g. startup_program_name, service_name, os_setting');
            $table->text('setting_value')->nullable()->comment('The baseline value for this configuration setting');
            $table->timestamp('baselined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['machine_id', 'setting_key'], 'cfg_baseline_unique');
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuration_baselines');
    }
};
