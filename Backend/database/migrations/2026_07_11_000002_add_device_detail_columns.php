<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_connected_devices', function (Blueprint $table) {
            $table->string('vendor_id', 100)->nullable()->after('manufacturer');
            $table->string('product_id', 100)->nullable()->after('vendor_id');
        });

        Schema::table('device_events', function (Blueprint $table) {
            $table->string('device_id', 255)->nullable()->after('device_name');
        });
    }

    public function down(): void
    {
        Schema::table('machine_connected_devices', function (Blueprint $table) {
            $table->dropColumn(['vendor_id', 'product_id']);
        });

        Schema::table('device_events', function (Blueprint $table) {
            $table->dropColumn('device_id');
        });
    }
};
