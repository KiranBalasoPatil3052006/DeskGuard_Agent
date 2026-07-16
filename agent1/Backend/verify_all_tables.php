<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RawPayloadLog;
use App\Models\Machine;
use App\Models\MachineCurrentStatus;
use App\Models\HealthLog;
use App\Models\MachineDisk;
use App\Models\MachineNetworkAdapter;
use App\Models\ProcessLog;
use App\Models\WindowsService;
use App\Models\AntivirusStatus;
use App\Models\FirewallStatus;
use App\Models\WindowsUpdate;
use App\Models\EventLog;

echo str_repeat("=", 80) . "\n";
echo "FULL DATA VERIFICATION FOR MACHINE: TEST-VERIFY-001\n";
echo str_repeat("=", 80) . "\n\n";

$machine = Machine::where('machine_uid', 'TEST-VERIFY-001')->first();
if (!$machine) {
    echo "ERROR: Machine not found!\n";
    exit(1);
}
echo "Machine ID: {$machine->id}\n";
echo "Hostname: {$machine->hostname}\n";
echo "OS: {$machine->operating_system} ({$machine->os_version})\n";
echo "Processor: {$machine->processor}\n";
echo "RAM (GB): {$machine->ram_gb}\n";
echo "Online: {$machine->is_online}\n";
echo "Last Heartbeat: {$machine->last_heartbeat_at}\n\n";

echo "--- MACHINE_CURRENT_STATUS ---\n";
$status = MachineCurrentStatus::where('machine_id', $machine->id)->first();
if ($status) {
    echo "  CPU%: {$status->cpu_percentage}, Temp: {$status->cpu_temperature}\n";
    echo "  RAM%: {$status->ram_percentage}, Used: {$status->ram_used_bytes}, Total: {$status->ram_total_bytes}\n";
    echo "  Disk%: {$status->disk_percentage}, Free: {$status->disk_free_bytes}\n";
    echo "  Battery%: {$status->battery_percentage}\n";
    echo "  Antivirus: {$status->antivirus_status}, Firewall: {$status->firewall_status}\n";
    echo "  Pending Updates: {$status->pending_updates}\n";
    echo "  Collected At: {$status->collected_at}\n";
} else {
    echo "  NOT FOUND!\n";
}

echo "\n--- HEALTH_LOGS ---\n";
$logs = HealthLog::where('machine_id', $machine->id)->get();
echo "  Count: " . $logs->count() . "\n";
foreach ($logs as $log) {
    echo "  CPU: {$log->cpu_percentage}, RAM%: {$log->ram_percentage}, Disk%: {$log->disk_percentage}, Batt%: {$log->battery_percentage}, Time: {$log->collected_at}\n";
}

echo "\n--- DISKS ---\n";
$disks = MachineDisk::where('machine_id', $machine->id)->get();
echo "  Count: " . $disks->count() . "\n";
foreach ($disks as $d) {
    echo "  {$d->drive_letter}: Total={$d->total_gb}GB, Used={$d->used_gb}GB, Free={$d->free_gb}GB, FS={$d->file_system}\n";
}

echo "\n--- NETWORK ADAPTERS ---\n";
$nets = MachineNetworkAdapter::where('machine_id', $machine->id)->get();
echo "  Count: " . $nets->count() . "\n";
foreach ($nets as $n) {
    echo "  {$n->adapter_name}: IP={$n->ip_address}, MAC={$n->mac_address}, Status={$n->status}, Speed={$n->speed}\n";
}

echo "\n--- PROCESS LOGS ---\n";
$procs = ProcessLog::where('machine_id', $machine->id)->get();
echo "  Count: " . $procs->count() . "\n";
foreach ($procs as $p) {
    echo "  {$p->process_name}: CPU={$p->cpu_usage}%, Mem={$p->memory_usage}MB\n";
}

echo "\n--- SERVICES ---\n";
$svcs = WindowsService::where('machine_id', $machine->id)->get();
echo "  Count: " . $svcs->count() . "\n";
foreach ($svcs as $s) {
    echo "  {$s->service_name}: Status={$s->status}, Start={$s->start_type}\n";
}

echo "\n--- ANTIVIRUS ---\n";
$av = AntivirusStatus::where('machine_id', $machine->id)->first();
if ($av) {
    echo "  Display: {$av->display_name}, Enabled: {$av->is_enabled}, Updated: {$av->is_updated}\n";
    echo "  RTP: {$av->real_time_protection}, DefStatus: {$av->definition_status}\n";
} else {
    echo "  NOT FOUND!\n";
}

echo "\n--- FIREWALL ---\n";
$fw = FirewallStatus::where('machine_id', $machine->id)->first();
if ($fw) {
    echo "  Enabled: {$fw->is_enabled}, Profile: {$fw->profile_name}\n";
} else {
    echo "  NOT FOUND!\n";
}

echo "\n--- UPDATES ---\n";
$updates = WindowsUpdate::where('machine_id', $machine->id)->get();
echo "  Count: " . $updates->count() . "\n";
foreach ($updates as $u) {
    echo "  Title: {$u->update_title}, Severity: {$u->severity}, Installed: {$u->is_installed}\n";
}

echo "\n--- EVENT LOGS ---\n";
$events = EventLog::where('machine_id', $machine->id)->get();
echo "  Count: " . $events->count() . "\n";
foreach ($events as $e) {
    echo "  [{$e->level}] {$e->log_name}/{$e->source}: {$e->message} at {$e->event_time}\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "VERIFICATION COMPLETE\n";
echo str_repeat("=", 80) . "\n";
