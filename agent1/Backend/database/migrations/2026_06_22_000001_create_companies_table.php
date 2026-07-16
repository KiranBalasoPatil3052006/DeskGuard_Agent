<?php

/**
 * Migration: Create companies table.
 * Stores multi-tenant company information.
 * Every entity in the system is scoped to a company.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255)->unique()->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('website', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            // Index for quick active company lookups
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
