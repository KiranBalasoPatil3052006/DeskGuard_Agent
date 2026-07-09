<?php

/**
 * Migration: Create startup_programs table.
 * Stores programs configured to run at system startup on each machine.
 * Used for startup optimization, malware persistence detection, and configuration auditing.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startup_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->string('program_name', 255);
            $table->text('program_path')->nullable();
            $table->string('startup_type', 255);
            $table->datetime('collected_at');
            $table->timestamps();
            // Indexes for startup program queries
            $table->index('company_id');
            $table->index('machine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_programs');
    }
};
