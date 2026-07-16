<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('change_history', function (Blueprint $table) {
            $table->string('status', 30)
                ->default('pending_review')
                ->after('severity')
                ->comment('pending_review, investigating, approved, resolved, false_positive');
            $table->text('recommendation')->nullable()->after('status')->comment('Recommended action for the change');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('change_history', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'recommendation']);
        });
    }
};
