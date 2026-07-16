<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add device_name column + index to alerts for fast device-issue lookups
        // Eliminates JSON metadata path query and LIKE '%x%' full table scan
        Schema::table('alerts', function (Blueprint $table) {
            $table->string('device_name', 255)->nullable()->after('machine_id');
            $table->index(['machine_id', 'device_name', 'created_at'], 'alerts_machine_device_created_idx');
        });

        // Composite index for connected devices lookup by machine + device name
        // Used by deviceIssues() endpoint
        Schema::table('machine_connected_devices', function (Blueprint $table) {
            $table->index(['machine_id', 'device_name', 'status'], 'connected_devices_machine_name_status_idx');
        });

        // Composite index for device events filtered by machine + device name + time
        // Used by deviceIssues() events sub-query
        Schema::table('device_events', function (Blueprint $table) {
            $table->index(['machine_id', 'device_name', 'event_time'], 'device_events_machine_name_time_idx');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex('alerts_machine_device_created_idx');
            $table->dropColumn('device_name');
        });

        Schema::table('machine_connected_devices', function (Blueprint $table) {
            $table->dropIndex('connected_devices_machine_name_status_idx');
        });

        Schema::table('device_events', function (Blueprint $table) {
            $table->dropIndex('device_events_machine_name_time_idx');
        });
    }
};
