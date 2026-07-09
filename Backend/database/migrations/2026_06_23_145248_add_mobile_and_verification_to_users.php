<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile_number', 20)->nullable()->unique()->after('id');
            $table->boolean('is_verified')->default(false)->after('name');
            $table->string('name', 255)->nullable()->change();
            $table->string('email', 255)->nullable()->change();
            $table->string('password', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mobile_number', 'is_verified']);
            $table->string('name', 255)->nullable(false)->change();
            $table->string('email', 255)->nullable(false)->change();
            $table->string('password', 255)->nullable(false)->change();
        });
    }
};
