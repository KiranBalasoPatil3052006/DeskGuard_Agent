<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create email_recipients table.
 *
 * Stores email addresses that should receive critical alert
 * notifications. Scoped per company so each company manages
 * its own recipient list independently.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                  ->constrained('companies')
                  ->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'email']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_recipients');
    }
};
