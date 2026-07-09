<?php

/**
 * Migration: Create login_activities table.
 * Tracks user login/logoff events and sessions on monitored machines.
 * Used for user behavior analytics, session tracking, and security incident response.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->string('event_type', 255);
            $table->string('username', 255)->nullable();
            $table->string('session_id', 255)->nullable();
            $table->datetime('logon_time')->nullable();
            $table->datetime('logoff_time')->nullable();
            $table->datetime('collected_at');
            $table->timestamps();
            // Indexes for login event queries
            $table->index('company_id');
            $table->index('machine_id');
            $table->index('event_type');
            $table->index('collected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_activities');
    }
};
