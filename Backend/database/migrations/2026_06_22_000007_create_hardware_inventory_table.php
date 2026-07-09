<?php

/**
 * Migration: Create hardware_inventory table.
 * Stores snapshot of hardware specifications for each machine.
 * Used for asset tracking, compliance auditing, and hardware lifecycle management.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardware_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->string('manufacturer', 255)->nullable();
            $table->string('model', 255)->nullable();
            $table->string('serial_number', 255)->nullable();
            $table->string('bios_version', 255)->nullable();
            $table->string('processor_name', 255)->nullable();
            $table->integer('processor_cores')->nullable();
            $table->integer('processor_threads')->nullable();
            $table->decimal('processor_clock_speed', 10, 2)->nullable();
            $table->decimal('ram_total_gb', 8, 2)->nullable();
            $table->string('ram_type', 255)->nullable();
            $table->string('disk_model', 255)->nullable();
            $table->string('disk_type', 255)->nullable();
            $table->decimal('disk_size_gb', 8, 2)->nullable();
            $table->string('gpu_name', 255)->nullable();
            $table->datetime('collected_at');
            $table->timestamps();
            // Indexes for asset queries
            $table->index('company_id');
            $table->index('machine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hardware_inventory');
    }
};
