<?php

/**
 * Migration: Create windows_updates table.
 * Stores pending/installed Windows Update information for each machine.
 * Used for patch compliance monitoring and vulnerability management.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('windows_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->string('update_title', 255);
            $table->text('update_description')->nullable();
            $table->string('severity', 255)->nullable();
            $table->string('category', 255)->nullable();
            $table->boolean('is_installed')->default(false);
            $table->datetime('collected_at');
            $table->timestamps();
            // Indexes for update compliance queries
            $table->index('company_id');
            $table->index('machine_id');
            $table->index('is_installed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('windows_updates');
    }
};
