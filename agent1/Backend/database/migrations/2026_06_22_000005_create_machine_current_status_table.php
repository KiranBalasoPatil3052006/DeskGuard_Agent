<?php

/**
 * Migration: Create machine_current_status table.
 * Stores the latest real-time hardware metrics for each machine (one row per machine).
 * Updated frequently by the agent. Provides quick dashboard access without querying logs.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_current_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete()->unique();
            $table->decimal('cpu_percentage', 5, 2)->nullable();
            $table->decimal('cpu_temperature', 5, 2)->nullable();
            $table->decimal('cpu_clock_speed', 10, 2)->nullable();
            $table->integer('cpu_core_count')->nullable();
            $table->bigInteger('ram_total_bytes')->nullable();
            $table->bigInteger('ram_used_bytes')->nullable();
            $table->bigInteger('ram_available_bytes')->nullable();
            $table->decimal('ram_percentage', 5, 2)->nullable();
            $table->bigInteger('disk_total_bytes')->nullable();
            $table->bigInteger('disk_used_bytes')->nullable();
            $table->bigInteger('disk_free_bytes')->nullable();
            $table->decimal('disk_percentage', 5, 2)->nullable();
            $table->string('disk_health_status', 255)->nullable();
            $table->decimal('battery_percentage', 5, 2)->nullable();
            $table->boolean('battery_charging_status')->nullable();
            $table->decimal('battery_wear_level', 5, 2)->nullable();
            $table->bigInteger('network_received_bytes')->nullable();
            $table->bigInteger('network_sent_bytes')->nullable();
            $table->boolean('online_status')->default(false);
            $table->timestamp('last_collected_at')->nullable();
            $table->timestamps();
            // Unique index enforced by FK declaration above
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_current_status');
    }
};
