<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('startup_programs', function (Blueprint $table) {
            if (!Schema::hasColumn('startup_programs', 'registry_key')) {
                $table->string('registry_key', 500)->nullable()->after('program_path');
            }
            if (!Schema::hasColumn('startup_programs', 'status')) {
                $table->string('status', 50)->default('enabled')->after('startup_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('startup_programs', function (Blueprint $table) {
            $table->dropColumn(['registry_key', 'status']);
        });
    }
};
