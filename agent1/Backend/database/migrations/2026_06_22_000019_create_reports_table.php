<?php

/**
 * Migration: Create reports table.
 * Stores metadata of generated reports (health, inventory, security, custom).
 * Reports are generated on-demand and stored as files with filter context.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->string('type', 255);
            $table->string('format', 255);
            $table->text('file_path')->nullable();
            $table->json('filters')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
            // Indexes for report queries
            $table->index('company_id');
            $table->index('type');
            $table->index('generated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
