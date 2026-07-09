<?php

/**
 * Migration: Create event_logs table.
 * Stores Windows Event Log entries (System, Application, Security) from monitored machines.
 * Used for security threat detection, troubleshooting, and compliance auditing.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->string('log_name', 255);
            $table->integer('event_id')->nullable();
            $table->string('level', 255);
            $table->string('source', 255)->nullable();
            $table->text('message')->nullable();
            $table->datetime('event_time')->nullable();
            $table->datetime('collected_at');
            $table->timestamps();
            // Indexes for event log queries
            $table->index('company_id');
            $table->index('machine_id');
            $table->index('log_name');
            $table->index('level');
            $table->index('event_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_logs');
    }
};
