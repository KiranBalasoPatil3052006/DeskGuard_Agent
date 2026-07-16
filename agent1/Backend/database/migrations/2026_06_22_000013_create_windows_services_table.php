<?php

/**
 * Migration: Create windows_services table.
 * Stores the current state of Windows services on each machine.
 * Used for service health monitoring and configuration drift detection.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('windows_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->string('service_name', 255);
            $table->string('display_name', 255)->nullable();
            $table->string('status', 255);
            $table->string('start_type', 255);
            $table->datetime('collected_at');
            $table->timestamps();
            // Indexes for service queries
            $table->index('company_id');
            $table->index('machine_id');
            $table->index('service_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('windows_services');
    }
};
