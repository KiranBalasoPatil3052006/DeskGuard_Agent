<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

DB::statement('SET FOREIGN_KEY_CHECKS=0');

$tables = [
    'machines', 'machine_current_status', 'health_logs', 'alerts',
    'device_events', 'machine_connected_devices', 'process_logs',
    'raw_payload_logs', 'event_logs', 'login_activities', 'usb_activities',
    'windows_services', 'windows_updates', 'startup_programs',
    'hardware_inventory', 'software_inventory', 'antivirus_status',
    'firewall_status', 'machine_disks', 'machine_network_adapters',
    'machine_tokens', 'audit_logs', 'notifications', 'reports', 'otp_codes',
];

foreach ($tables as $table) {
    DB::table($table)->truncate();
    echo "Cleared: $table\n";
}

DB::statement('SET FOREIGN_KEY_CHECKS=1');

echo "\nAll test data cleared successfully. Schema, indexes, constraints preserved.\n";
echo "Companies, users, roles, permissions, alert_rules, email_recipients, personal_access_tokens preserved.\n";