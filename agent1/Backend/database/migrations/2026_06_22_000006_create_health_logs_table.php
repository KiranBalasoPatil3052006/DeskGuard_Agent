<?php

/**
 * Migration: Create health_logs table.
 * Append-only time-series storage for historical health/metrics data collected from machines.
 * Used for trend analysis, graphing, and historical reporting.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->decimal('cpu_percentage', 5, 2)->nullable();
            $table->decimal('cpu_temperature', 5, 2)->nullable();
            $table->decimal('ram_percentage', 5, 2)->nullable();
            $table->bigInteger('ram_used_bytes')->nullable();
            $table->bigInteger('ram_available_bytes')->nullable();
            $table->bigInteger('ram_total_bytes')->nullable();
            $table->decimal('disk_percentage', 5, 2)->nullable();
            $table->bigInteger('disk_free_bytes')->nullable();
            $table->bigInteger('disk_total_bytes')->nullable();
            $table->decimal('battery_percentage', 5, 2)->nullable();
            $table->boolean('online_status')->default(false);
            $table->datetime('collected_at');
            $table->timestamps();
            // Indexes for time-series queries
            $table->index('company_id');
            $table->index('machine_id');
            $table->index('collected_at');
            $table->index(['machine_id', 'collected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_logs');
    }
};
