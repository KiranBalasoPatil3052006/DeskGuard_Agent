<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$m = App\Models\Machine::where('machine_uid', 'TEST-VERIFY-003')->first();
if ($m) {
    App\Models\MachineCurrentStatus::where('machine_id', $m->id)->delete();
    App\Models\MachineDisk::where('machine_id', $m->id)->delete();
    App\Models\MachineNetworkAdapter::where('machine_id', $m->id)->delete();
    App\Models\AntivirusStatus::where('machine_id', $m->id)->delete();
    App\Models\FirewallStatus::where('machine_id', $m->id)->delete();
    App\Models\WindowsUpdate::where('machine_id', $m->id)->delete();
    App\Models\WindowsService::where('machine_id', $m->id)->delete();
    App\Models\EventLog::where('machine_id', $m->id)->delete();
    App\Models\HealthLog::where('machine_id', $m->id)->delete();
    $m->delete();
    echo "Cleaned old machine 8\n";
}

echo "Now send the health payload again...\n";
echo "After sending, run: php _verify_final.php\n";
