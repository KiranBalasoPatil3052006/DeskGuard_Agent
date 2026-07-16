<?php

/**
 * Migration: Create firewall_status table.
 * Stores the current state of Windows Firewall (or equivalent) on each machine.
 * Used for network security monitoring and compliance auditing.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firewall_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->boolean('is_enabled');
            $table->string('profile_name', 255)->nullable();
            $table->datetime('collected_at');
            $table->timestamps();
            // Indexes for firewall queries
            $table->index('company_id');
            $table->index('machine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firewall_status');
    }
};
