<?php

/**
 * Migration: Create alerts table.
 * Stores generated alerts triggered by alert rules or system events.
 * Supports lifecycle management with acknowledgment and resolution tracking.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->foreignId('alert_rule_id')->nullable()->constrained('alert_rules')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('severity', 255);
            $table->string('status', 255)->default('open');
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            // Indexes for alert queries
            $table->index('company_id');
            $table->index('machine_id');
            $table->index('severity');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
