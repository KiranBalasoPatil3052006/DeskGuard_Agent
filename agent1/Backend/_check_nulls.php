<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Machine;
use App\Models\MachineCurrentStatus;
use App\Models\HealthLog;
use App\Models\MachineNetworkAdapter;
use App\Models\AntivirusStatus;
use App\Models\WindowsUpdate;

$m = Machine::where('machine_uid', 'TEST-VERIFY-001')->first();
$id = $m->id;

echo "=== MACHINE_CURRENT_STATUS ===\n";
$s = MachineCurrentStatus::where('machine_id', $id)->first();
$cols = ['cpu_percentage','cpu_temperature','ram_percentage','ram_used_bytes','ram_total_bytes',
         'disk_percentage','disk_free_bytes','battery_percentage','antivirus_status','firewall_status',
         'pending_updates','collected_at'];
foreach ($cols as $c) {
    echo "$c: " . var_export($s->$c ?? null, true) . "\n";
}

echo "\n=== HEALTH_LOGS ===\n";
$logs = HealthLog::where('machine_id', $id)->get();
foreach ($logs as $l) {
    echo "id={$l->id}: cpu=" . var_export($l->cpu_percentage, true)
        . " ram=" . var_export($l->ram_percentage, true)
        . " disk=" . var_export($l->disk_percentage, true)
        . " batt=" . var_export($l->battery_percentage, true)
        . " type={$l->metric_type}\n";
}

echo "\n=== NETWORK ADAPTERS ===\n";
$nets = MachineNetworkAdapter::where('machine_id', $id)->get();
foreach ($nets as $n) {
    echo "{$n->adapter_name}: speed=" . var_export($n->speed, true) . " ip=" . var_export($n->ip_address, true) . " mac=" . var_export($n->mac_address, true) . "\n";
}

echo "\n=== ANTIVIRUS ===\n";
$av = AntivirusStatus::where('machine_id', $id)->first();
echo "definition_status: " . var_export($av->definition_status ?? null, true) . "\n";
echo "real_time_protection: " . var_export($av->real_time_protection ?? null, true) . "\n";

echo "\n=== WINDOWS UPDATES ===\n";
$updates = WindowsUpdate::where('machine_id', $id)->get();
foreach ($updates as $u) {
    echo "id={$u->id}: is_installed=" . var_export($u->is_installed, true) . " update_title=" . var_export($u->update_title, true) . " severity=" . var_export($u->severity, true) . "\n";
}

echo "\nDone.\n";
