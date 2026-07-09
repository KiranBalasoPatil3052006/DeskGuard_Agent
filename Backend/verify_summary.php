<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

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
use App\Models\RawPayloadLog;

// Check all machines
$machines = Machine::latest()->get();
echo "Total machines: " . $machines->count() . "\n\n";

foreach ($machines as $machine) {
    echo "=== Machine: {$machine->machine_uid} ===\n";
    echo "  ID: {$machine->id}, Hostname: {$machine->hostname}, OS: {$machine->operating_system}, RAM: {$machine->ram_gb}GB, CPU: {$machine->processor}\n";
    
    $status = MachineCurrentStatus::where('machine_id', $machine->id)->first();
    echo "  Status: " . ($status ? "CPU={$status->cpu_percentage}%, RAM={$status->ram_percentage}%, Disk={$status->disk_percentage}%" : "EMPTY") . "\n";
    
    $hlCount = HealthLog::where('machine_id', $machine->id)->count();
    echo "  HealthLogs: $hlCount\n";
    
    $diskCount = MachineDisk::where('machine_id', $machine->id)->count();
    echo "  Disks: $diskCount\n";
    
    $netCount = MachineNetworkAdapter::where('machine_id', $machine->id)->count();
    echo "  Network: $netCount\n";
    
    $procCount = ProcessLog::where('machine_id', $machine->id)->count();
    echo "  Processes: $procCount\n";
    
    $svcCount = WindowsService::where('machine_id', $machine->id)->count();
    echo "  Services: $svcCount\n";
    
    $av = AntivirusStatus::where('machine_id', $machine->id)->first();
    echo "  Antivirus: " . ($av ? $av->display_name . " (" . ($av->is_enabled ? 'enabled' : 'disabled') . ")" : "EMPTY") . "\n";
    
    $fw = FirewallStatus::where('machine_id', $machine->id)->first();
    echo "  Firewall: " . ($fw ? ($fw->is_enabled ? 'enabled' : 'disabled') . ", Profile: " . $fw->profile_name : "EMPTY") . "\n";
    
    $updCount = WindowsUpdate::where('machine_id', $machine->id)->count();
    echo "  Updates: $updCount\n";
    
    $evtCount = EventLog::where('machine_id', $machine->id)->count();
    echo "  Events: $evtCount\n";
    
    $rawCount = RawPayloadLog::where('machine_id', $machine->id)->count();
    echo "  RawPayloads: $rawCount\n";
    echo "\n";
}
