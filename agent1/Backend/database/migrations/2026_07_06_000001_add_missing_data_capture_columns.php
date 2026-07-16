<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            if (!Schema::hasColumn('machines', 'domain_name')) {
                $table->string('domain_name', 255)->nullable()->after('device_name');
            }
            if (!Schema::hasColumn('machines', 'architecture')) {
                $table->string('architecture', 50)->nullable()->after('operating_system');
            }
            if (!Schema::hasColumn('machines', 'uptime_seconds')) {
                $table->bigInteger('uptime_seconds')->nullable()->after('os_version');
            }
            if (!Schema::hasColumn('machines', 'current_logged_in_users')) {
                $table->text('current_logged_in_users')->nullable()->after('uptime_seconds');
            }
        });

        Schema::table('hardware_inventory', function (Blueprint $table) {
            if (!Schema::hasColumn('hardware_inventory', 'bios_vendor')) {
                $table->string('bios_vendor', 255)->nullable()->after('bios_version');
            }
            if (!Schema::hasColumn('hardware_inventory', 'bios_release_date')) {
                $table->string('bios_release_date', 50)->nullable()->after('bios_vendor');
            }
            if (!Schema::hasColumn('hardware_inventory', 'system_architecture')) {
                $table->string('system_architecture', 50)->nullable()->after('processor_clock_speed');
            }
        });

        Schema::table('machine_disks', function (Blueprint $table) {
            if (!Schema::hasColumn('machine_disks', 'volume_label')) {
                $table->string('volume_label', 255)->nullable()->after('drive_letter');
            }
        });

        Schema::table('process_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('process_logs', 'process_id')) {
                $table->integer('process_id')->nullable()->after('process_name');
            }
            if (!Schema::hasColumn('process_logs', 'executable_path')) {
                $table->text('executable_path')->nullable()->after('process_id');
            }
            if (!Schema::hasColumn('process_logs', 'thread_count')) {
                $table->integer('thread_count')->nullable()->after('executable_path');
            }
            if (!Schema::hasColumn('process_logs', 'user_name')) {
                $table->string('user_name', 255)->nullable()->after('thread_count');
            }
        });

        Schema::table('machine_network_adapters', function (Blueprint $table) {
            if (!Schema::hasColumn('machine_network_adapters', 'ip_address_v6')) {
                $table->string('ip_address_v6', 45)->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('machine_network_adapters', 'adapter_type')) {
                $table->string('adapter_type', 50)->nullable()->after('mac_address');
            }
        });

        Schema::table('machine_current_status', function (Blueprint $table) {
            if (!Schema::hasColumn('machine_current_status', 'battery_is_present')) {
                $table->boolean('battery_is_present')->nullable()->after('battery_wear_level');
            }
            if (!Schema::hasColumn('machine_current_status', 'battery_design_capacity')) {
                $table->integer('battery_design_capacity')->nullable()->after('battery_is_present');
            }
            if (!Schema::hasColumn('machine_current_status', 'battery_full_charge_capacity')) {
                $table->integer('battery_full_charge_capacity')->nullable()->after('battery_design_capacity');
            }
        });

        Schema::table('software_inventory', function (Blueprint $table) {
            if (!Schema::hasColumn('software_inventory', 'registry_key_path')) {
                $table->text('registry_key_path')->nullable()->after('architecture');
            }
            if (!Schema::hasColumn('software_inventory', 'estimated_size_mb')) {
                $table->decimal('estimated_size_mb', 10, 2)->nullable()->after('registry_key_path');
            }
        });

        Schema::table('windows_services', function (Blueprint $table) {
            if (!Schema::hasColumn('windows_services', 'service_type')) {
                $table->string('service_type', 100)->nullable()->after('start_type');
            }
            if (!Schema::hasColumn('windows_services', 'log_on_as')) {
                $table->string('log_on_as', 255)->nullable()->after('service_type');
            }
        });
    }

    public function down(): void
    {
        $columns = [
            'machines'                     => ['domain_name', 'architecture', 'uptime_seconds', 'current_logged_in_users'],
            'hardware_inventory'           => ['bios_vendor', 'bios_release_date', 'system_architecture'],
            'machine_disks'                => ['volume_label'],
            'process_logs'                 => ['process_id', 'executable_path', 'thread_count', 'user_name'],
            'machine_network_adapters'     => ['ip_address_v6', 'adapter_type'],
            'machine_current_status'       => ['battery_is_present', 'battery_design_capacity', 'battery_full_charge_capacity'],
            'software_inventory'           => ['registry_key_path', 'estimated_size_mb'],
            'windows_services'             => ['service_type', 'log_on_as'],
        ];

        foreach ($columns as $table => $cols) {
            Schema::table($table, function (Blueprint $table) use ($cols) {
                $table->dropColumn($cols);
            });
        }
    }
};
