<?php

/**
 * Migration: Create machines table.
 * Stores hardware endpoints (desktops/laptops/servers) monitored by the system.
 * Each machine is assigned to a company and optionally linked to a user (employee).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('machine_uid', 255)->unique();
            $table->string('hostname', 255)->nullable();
            $table->string('operating_system', 255)->nullable();
            $table->string('os_version', 255)->nullable();
            $table->string('manufacturer', 255)->nullable();
            $table->string('model', 255)->nullable();
            $table->string('serial_number', 255)->nullable();
            $table->string('bios_version', 255)->nullable();
            $table->string('processor', 255)->nullable();
            $table->integer('ram_gb')->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->string('activation_token', 255)->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            // Indexes for filtering and lookups
            $table->index('company_id');
            $table->index('user_id');
            $table->index('machine_uid');
            $table->index('is_online');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
