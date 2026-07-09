<?php

/**
 * Migration: Create software_inventory table.
 * Stores inventory of installed software applications on each machine.
 * Used for license compliance, security auditing, and software lifecycle management.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->string('software_name', 255);
            $table->string('version', 255)->nullable();
            $table->string('publisher', 255)->nullable();
            $table->date('install_date')->nullable();
            $table->string('architecture', 255)->nullable();
            $table->datetime('collected_at');
            $table->timestamps();
            // Indexes for software lookups
            $table->index('company_id');
            $table->index('machine_id');
            $table->index('software_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_inventory');
    }
};
