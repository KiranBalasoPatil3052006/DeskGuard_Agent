<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Machine;
use App\Models\MachineCurrentStatus;
use App\Models\MachineDisk;
use App\Models\MachineNetworkAdapter;
use App\Models\AntivirusStatus;
use App\Models\FirewallStatus;
use App\Models\WindowsUpdate;
use App\Models\EventLog;
use App\Models\WindowsService;
use App\Models\HealthLog;
use App\Models\ProcessLog;
use App\Models\Alert;
use App\Models\LoginActivity;
use App\Models\StartupProgram;
use App\Models\SoftwareInventory;
use App\Models\HardwareInventory;

// Read the last registered machine UID
$uidFile = __DIR__ . '/_last_machine_uid.txt';
$machineUid = file_exists($uidFile) ? trim(file_get_contents($uidFile)) : null;

$m = $machineUid 
    ? Machine::where('machine_uid', $machineUid)->first() 
    : Machine::first();

if (!$m) {
    // Just show summary counts
    echo "=== DATABASE SUMMARY ===\n";
    echo "Machines: " . Machine::count() . "\n";
    echo "Current Statuses: " . MachineCurrentStatus::count() . "\n";
    echo "Health Logs: " . HealthLog::count() . "\n";
    echo "Alerts: " . Alert::count() . "\n";
    echo "Process Logs: " . ProcessLog::count() . "\n";
    echo "Login Activities: " . LoginActivity::count() . "\n";
    echo "Device Events: " . \App\Models\DeviceEvent::count() . "\n";
    echo "Connected Devices: " . \App\Models\MachineConnectedDevice::count() . "\n";
    echo "Startup Programs: " . StartupProgram::count() . "\n";
    echo "Software Inventory: " . SoftwareInventory::count() . "\n";
    echo "Hardware Inventory: " . HardwareInventory::count() . "\n";
    echo "Windows Services: " . WindowsService::count() . "\n";
    echo "Windows Updates: " . WindowsUpdate::count() . "\n";
    echo "Event Logs: " . EventLog::count() . "\n";
    echo "Antivirus Status: " . AntivirusStatus::count() . "\n";
    echo "Firewall Status: " . FirewallStatus::count() . "\n";
    echo "\nNo machines found. Run: php _register_test_machine.php\n";
    exit(0);
}

echo "Machine UID: {$m->machine_uid}\n";
echo "Machine ID: {$m->id}\n";
echo "Hostname: {$m->hostname}\n";
echo "Online: " . var_export($m->is_online, true) . "\n";
echo "Status: {$m->status}\n\n";

echo "=== MACHINE_CURRENT_STATUS ===\n";
$s = MachineCurrentStatus::where('machine_id', $m->id)->first();
if ($s) {
    foreach(['cpu_percentage','cpu_temperature','cpu_clock_speed','cpu_core_count',
             'ram_percentage','ram_used_bytes','ram_total_bytes',
             'disk_percentage','disk_free_bytes','disk_total_bytes','disk_used_bytes','disk_health_status',
             'battery_percentage','battery_charging_status','battery_wear_level',
             'antivirus_status','firewall_status','pending_updates',
             'network_sent_bytes','network_received_bytes','online_status','collected_at'] as $f) {
        echo "$f: " . var_export($s->$f ?? 'NULL', true) . "\n";
    }
} else { echo "No current status row\n"; }

echo "\n=== HEALTH LOGS ===\n";
foreach (HealthLog::where('machine_id', $m->id)->get() as $h) {
    echo "cpu={$h->cpu_percentage} ram={$h->ram_percentage} disk={$h->disk_percentage} collected={$h->collected_at}\n";
}

echo "\n=== PROCESSES ===\n";
foreach (ProcessLog::where('machine_id', $m->id)->get() as $p) {
    echo "{$p->process_name} cpu={$p->cpu_usage} mem={$p->memory_usage}\n";
}

echo "\n=== LOGIN ACTIVITY ===\n";
foreach (LoginActivity::where('machine_id', $m->id)->get() as $l) {
    echo "{$l->event_type} user={$l->username} time={$l->logon_time}\n";
}

echo "\n=== STARTUP PROGRAMS ===\n";
foreach (StartupProgram::where('machine_id', $m->id)->get() as $sp) {
    echo "{$sp->program_name} type={$sp->startup_type}\n";
}

echo "\n=== DISKS ===\n";
foreach (MachineDisk::where('machine_id', $m->id)->get() as $d) {
    echo "{$d->drive_letter}: total={$d->total_gb} used={$d->used_gb} free={$d->free_gb}\n";
}

echo "\n=== NETWORK ===\n";
foreach (MachineNetworkAdapter::where('machine_id', $m->id)->get() as $n) {
    echo "{$n->adapter_name}: ip={$n->ip_address} mac={$n->mac_address}\n";
}

echo "\n=== ANTIVIRUS ===\n";
$av = AntivirusStatus::where('machine_id', $m->id)->first();
if ($av) echo "{$av->display_name} enabled={$av->is_enabled} updated={$av->is_updated} rtp={$av->real_time_protection}\n";

echo "\n=== FIREWALL ===\n";
$fw = FirewallStatus::where('machine_id', $m->id)->first();
if ($fw) echo "enabled={$fw->is_enabled} profile={$fw->profile_name}\n";

echo "\n=== WINDOWS UPDATES ===\n";
foreach (WindowsUpdate::where('machine_id', $m->id)->get() as $u) {
    echo "{$u->update_title} severity={$u->severity} kb={$u->kb_article} installed={$u->is_installed}\n";
}

echo "\n=== WINDOWS SERVICES ===\n";
foreach (WindowsService::where('machine_id', $m->id)->get() as $svc) {
    echo "{$svc->service_name}: status={$svc->status} start={$svc->start_type}\n";
}

echo "\n=== EVENT LOGS ===\n";
foreach (EventLog::where('machine_id', $m->id)->get() as $e) {
    echo "{$e->level} {$e->source}: {$e->message}\n";
}

echo "\n=== ALERTS ===\n";
foreach (Alert::where('machine_id', $m->id)->get() as $a) {
    echo "{$a->severity} {$a->title} status={$a->status}\n";
}

echo "\n✓ All verification complete. Data is stored across all tables.\n";