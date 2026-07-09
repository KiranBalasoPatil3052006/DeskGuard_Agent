<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->string('device_name', 255)->nullable()->after('hostname');
            $table->string('status', 50)->default('pending')->after('is_active');
            $table->string('api_token', 100)->nullable()->after('activation_token');
            $table->index('api_token');
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn(['device_name', 'status', 'api_token']);
        });
    }
};
