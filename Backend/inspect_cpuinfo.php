<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RawPayloadLog;

$raw = RawPayloadLog::where('machine_uid', 'not like', 'TEST%')->latest()->first();
if ($raw) {
    $payload = json_decode($raw->payload, true);
    echo "=== cpuInfo ===\n";
    if (isset($payload['cpuInfo'])) {
        foreach ($payload['cpuInfo'] as $k => $v) {
            echo "  $k: " . (is_scalar($v) ? $v : json_encode($v)) . "\n";
        }
    }
    echo "\n=== antivirusInfo ===\n";
    if (isset($payload['antivirusInfo'])) {
        foreach ($payload['antivirusInfo'] as $k => $v) {
            echo "  $k: " . (is_scalar($v) ? $v : json_encode($v)) . "\n";
        }
    }
    echo "\n=== firewallInfo ===\n";
    if (isset($payload['firewallInfo'])) {
        foreach ($payload['firewallInfo'] as $k => $v) {
            echo "  $k: " . (is_scalar($v) ? $v : json_encode($v)) . "\n";
        }
    }
    echo "\n=== updateInfo ===\n";
    if (isset($payload['updateInfo'])) {
        foreach ($payload['updateInfo'] as $k => $v) {
            echo "  $k: " . (is_scalar($v) ? $v : json_encode($v)) . "\n";
        }
    }
}
