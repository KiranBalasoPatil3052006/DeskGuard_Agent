<?php

/**
 * Migration: Create alert_rules table.
 * Stores configurable threshold-based alert rules for machine metrics and events.
 * Supports multi-tenant alerting with customizable severity and cooldown periods.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('metric', 255);
            $table->string('condition', 255);
            $table->string('threshold', 255)->nullable();
            $table->string('severity', 255);
            $table->boolean('is_enabled')->default(true);
            $table->integer('consecutive_count')->default(1);
            $table->integer('cooldown_minutes')->default(30);
            $table->timestamps();
            // Indexes for rule evaluation
            $table->index('company_id');
            $table->index('metric');
            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
