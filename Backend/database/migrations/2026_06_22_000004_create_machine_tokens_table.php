<?php

/**
 * Migration: Create machine_tokens table.
 * Stores API tokens for machine authentication (separate from Sanctum user tokens).
 * Used by the agent software to authenticate with the backend.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->string('token', 255)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            // Index for token lookups
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_tokens');
    }
};
