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
use App\Models\AntivirusStatus;
use App\Models\FirewallStatus;
use App\Models\WindowsUpdate;
use App\Models\WindowsService;

echo "=== RAW PAYLOADS ===\n";
RawPayloadLog::latest()->take(2)->get()->each(function($r) {
    echo "ID: {$r->id}, MachineID: {$r->machine_id}, Time: {$r->received_at}\n";
});

echo "\n=== MACHINES ===\n";
Machine::latest()->take(2)->get()->each(function($m) {
    echo "ID: {$m->id}, UID: {$m->machine_uid}, Hostname: {$m->hostname}, OS: {$m->operating_system}\n";
});

echo "\n=== MACHINE CURRENT STATUS ===\n";
MachineCurrentStatus::latest()->take(5)->get()->each(function($s) {
    echo "ID: {$s->id}, MachineID: {$s->machine_id}, CPU%: {$s->cpu_percentage}, RAM%: {$s->ram_percentage}, Disk%: {$s->disk_percentage}, AV: {$s->antivirus_status}, FW: {$s->firewall_status}, PendingUpdates: {$s->pending_updates}\n";
});

echo "\n=== HEALTH LOGS (CPU) ===\n";
HealthLog::where('metric_name', 'like', '%cpu%')->latest()->take(3)->get()->each(function($h) {
    echo "ID: {$h->id}, MachineID: {$h->machine_id}, Metric: {$h->metric_name}, Value: {$h->metric_value}\n";
});

echo "\n=== DISKS ===\n";
MachineDisk::latest()->take(3)->get()->each(function($d) {
    echo "ID: {$d->id}, MachineID: {$d->machine_id}, Name: {$d->disk_name}, Total: {$d->total_bytes}, Free: {$d->free_bytes}\n";
});

echo "\n=== ANTIVIRUS ===\n";
AntivirusStatus::latest()->take(3)->get()->each(function($a) {
    echo "ID: {$a->id}, DisplayName: {$a->display_name}, Enabled: {$a->is_enabled}, Updated: {$a->is_updated}, RTP: {$a->real_time_protection}, DefStatus: {$a->definition_status}\n";
});

echo "\n=== FIREWALL ===\n";
FirewallStatus::latest()->take(3)->get()->each(function($f) {
    echo "ID: {$f->id}, Enabled: {$f->is_enabled}, Profile: {$f->profile_name}\n";
});

echo "\n=== WINDOWS UPDATES ===\n";
WindowsUpdate::latest()->take(3)->get()->each(function($u) {
    echo "ID: {$u->id}, Title: {$u->update_title}, Severity: {$u->severity}, Installed: {$u->is_installed}\n";
});

echo "\n=== WINDOWS SERVICES ===\n";
WindowsService::latest()->take(5)->get()->each(function($s) {
    echo "ID: {$s->id}, Service: {$s->service_name}, Status: {$s->status}, StartType: {$s->start_type}\n";
});

echo "\nDONE\n";
