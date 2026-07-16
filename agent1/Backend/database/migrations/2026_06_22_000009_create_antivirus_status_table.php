<?php

/**
 * Migration: Create antivirus_status table.
 * Stores the current status of antivirus/anti-malware solutions on each machine.
 * Used for security compliance monitoring and threat posture assessment.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antivirus_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->string('display_name', 255);
            $table->boolean('is_enabled');
            $table->boolean('is_updated');
            $table->string('definition_status', 255)->nullable();
            $table->boolean('real_time_protection')->nullable();
            $table->datetime('collected_at');
            $table->timestamps();
            // Indexes for security queries
            $table->index('company_id');
            $table->index('machine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antivirus_status');
    }
};
