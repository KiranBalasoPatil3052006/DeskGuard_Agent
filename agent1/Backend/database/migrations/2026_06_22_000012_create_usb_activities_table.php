<?php

/**
 * Migration: Create usb_activities table.
 * Tracks USB device plug/unplug events on monitored machines.
 * Used for data exfiltration detection, device compliance, and peripheral auditing.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usb_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->string('device_name', 255);
            $table->string('device_serial', 255)->nullable();
            $table->string('drive_letter', 255)->nullable();
            $table->string('event_type', 255);
            $table->datetime('collected_at');
            $table->timestamps();
            // Indexes for USB activity queries
            $table->index('company_id');
            $table->index('machine_id');
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usb_activities');
    }
};
