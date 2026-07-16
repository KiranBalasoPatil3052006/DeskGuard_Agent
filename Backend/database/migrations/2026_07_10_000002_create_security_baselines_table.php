<?php

/**
 * Migration: Create security_baselines table.
 *
 * Stores the approved security state for each machine,
 * including antivirus status, firewall status, and other
 * security posture indicators. Used by the comparison engine
 * to detect security configuration drifts.
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
        Schema::create('security_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('component')->comment('e.g. antivirus_display_name, firewall_enabled, real_time_protection, signature_up_to_date');
            $table->text('value')->nullable()->comment('The baseline value for this security component');
            $table->timestamp('baselined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['machine_id', 'component'], 'sec_baseline_unique');
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_baselines');
    }
};
