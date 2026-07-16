<?php

/**
 * Migration: Add severity column to change_history table.
 *
 * This column enables the Severity Engine (Phase 6) to classify
 * changes automatically (information, warning, important, critical)
 * and allows the frontend to render severity-based badges and filters.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds the severity column and an index for efficient filtering.
     */
    public function up(): void
    {
        Schema::table('change_history', function (Blueprint $table) {
            $table->string('severity', 20)
                ->default('information')
                ->after('change_type')
                ->comment('information, warning, important, critical');
            $table->index('severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_history', function (Blueprint $table) {
            $table->dropIndex(['severity']);
            $table->dropColumn('severity');
        });
    }
};
