<?php

/**
 * Migration: Create audit_logs table.
 * Stores security audit trail for all significant system events.
 * Provides an immutable record of who did what, when, and from where.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('machine_id')->nullable()->constrained('machines')->cascadeOnDelete();
            $table->string('event_type', 255);
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 255)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
            // Indexes for audit trail queries
            $table->index('company_id');
            $table->index('user_id');
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
