<?php

/**
 * Migration: Create notifications table.
 * Stores in-app notifications for users about alerts, reports, and system events.
 * Supports read/unread tracking with read timestamps.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->string('type', 255);
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            // Indexes for user notification queries
            $table->index('company_id');
            $table->index('user_id');
            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
