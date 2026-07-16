<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // health_logs: Machine-level history/chart queries
        // WHERE machine_id = ? AND collected_at BETWEEN ? AND ? ORDER BY collected_at ASC
        Schema::table('health_logs', function (Blueprint $table) {
            $table->index(['machine_id', 'collected_at'], 'health_logs_machine_collected_idx');
        });

        // process_logs: Top CPU consumers by machine
        // WHERE machine_id = ? ORDER BY cpu_usage DESC LIMIT 100
        Schema::table('process_logs', function (Blueprint $table) {
            $table->index(['machine_id', 'cpu_usage'], 'process_logs_machine_cpu_idx');
        });

        // alerts: Machine-level alert filtering
        // WHERE machine_id = ? AND severity = ? AND status IN (?) ORDER BY created_at DESC
        Schema::table('alerts', function (Blueprint $table) {
            $table->index(['machine_id', 'severity', 'status', 'created_at'], 'alerts_machine_severity_status_created_idx');
        });

        // event_logs: Event log queries by machine
        // WHERE machine_id = ? ORDER BY event_time DESC LIMIT 100
        Schema::table('event_logs', function (Blueprint $table) {
            $table->index(['machine_id', 'event_time'], 'event_logs_machine_event_time_idx');
        });

        // windows_services: Service queries by machine
        // WHERE machine_id = ? ORDER BY display_name ASC
        Schema::table('windows_services', function (Blueprint $table) {
            $table->index(['machine_id', 'display_name'], 'windows_services_machine_display_idx');
        });

        // windows_updates: Update queries by machine
        // WHERE machine_id = ? ORDER BY created_at DESC
        Schema::table('windows_updates', function (Blueprint $table) {
            $table->index(['machine_id', 'created_at'], 'windows_updates_machine_created_idx');
        });

        // startup_programs: Startup program queries by machine
        // WHERE machine_id = ? ORDER BY program_name ASC
        Schema::table('startup_programs', function (Blueprint $table) {
            $table->index(['machine_id', 'program_name'], 'startup_programs_machine_program_idx');
        });

        // machine_network_adapters: Network queries by machine
        // WHERE machine_id = ? ORDER BY adapter_name ASC
        Schema::table('machine_network_adapters', function (Blueprint $table) {
            $table->index(['machine_id', 'adapter_name'], 'network_adapters_machine_adapter_idx');
        });

        // machine_disks: Disk queries by machine
        // WHERE machine_id = ? ORDER BY drive_letter ASC
        Schema::table('machine_disks', function (Blueprint $table) {
            $table->index(['machine_id', 'drive_letter'], 'machine_disks_machine_drive_idx');
        });

        // hardware_inventory: Latest hardware by machine
        // WHERE machine_id = ? ORDER BY collected_at DESC LIMIT 1
        Schema::table('hardware_inventory', function (Blueprint $table) {
            $table->index(['machine_id', 'collected_at'], 'hardware_inventory_machine_collected_idx');
        });

        // antivirus_status: Latest antivirus by machine
        // WHERE machine_id = ? ORDER BY collected_at DESC LIMIT 1
        Schema::table('antivirus_status', function (Blueprint $table) {
            $table->index(['machine_id', 'collected_at'], 'antivirus_status_machine_collected_idx');
        });

        // firewall_status: Latest firewall by machine
        // WHERE machine_id = ? ORDER BY collected_at DESC LIMIT 1
        Schema::table('firewall_status', function (Blueprint $table) {
            $table->index(['machine_id', 'collected_at'], 'firewall_status_machine_collected_idx');
        });
    }

    public function down(): void
    {
        Schema::table('health_logs', fn(Blueprint $t) => $t->dropIndex('health_logs_machine_collected_idx'));
        Schema::table('process_logs', fn(Blueprint $t) => $t->dropIndex('process_logs_machine_cpu_idx'));
        Schema::table('alerts', fn(Blueprint $t) => $t->dropIndex('alerts_machine_severity_status_created_idx'));
        Schema::table('event_logs', fn(Blueprint $t) => $t->dropIndex('event_logs_machine_event_time_idx'));
        Schema::table('windows_services', fn(Blueprint $t) => $t->dropIndex('windows_services_machine_display_idx'));
        Schema::table('windows_updates', fn(Blueprint $t) => $t->dropIndex('windows_updates_machine_created_idx'));
        Schema::table('startup_programs', fn(Blueprint $t) => $t->dropIndex('startup_programs_machine_program_idx'));
        Schema::table('machine_network_adapters', fn(Blueprint $t) => $t->dropIndex('network_adapters_machine_adapter_idx'));
        Schema::table('machine_disks', fn(Blueprint $t) => $t->dropIndex('machine_disks_machine_drive_idx'));
        Schema::table('hardware_inventory', fn(Blueprint $t) => $t->dropIndex('hardware_inventory_machine_collected_idx'));
        Schema::table('antivirus_status', fn(Blueprint $t) => $t->dropIndex('antivirus_status_machine_collected_idx'));
        Schema::table('firewall_status', fn(Blueprint $t) => $t->dropIndex('firewall_status_machine_collected_idx'));
    }
};
