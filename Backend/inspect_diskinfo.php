<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RawPayloadLog;

$raw = RawPayloadLog::where('machine_uid', 'not like', 'TEST%')->latest()->first();
if ($raw) {
    $payload = json_decode($raw->payload, true);
    echo "=== diskInfo ===\n";
    if (isset($payload['diskInfo']) && is_array($payload['diskInfo'])) {
        foreach ($payload['diskInfo'] as $i => $disk) {
            echo "Disk $i:\n";
            foreach ($disk as $k => $v) {
                echo "  $k: " . (is_scalar($v) ? $v : json_encode($v)) . "\n";
            }
        }
    }
    echo "\n=== networkInfo ===\n";
    if (isset($payload['networkInfo']) && is_array($payload['networkInfo'])) {
        foreach ($payload['networkInfo'] as $i => $net) {
            echo "Adapter $i:\n";
            foreach ($net as $k => $v) {
                echo "  $k: " . (is_scalar($v) ? $v : json_encode($v)) . "\n";
            }
        }
    }
    echo "\n=== processInfo (first 3) ===\n";
    if (isset($payload['processInfo']) && is_array($payload['processInfo'])) {
        foreach (array_slice($payload['processInfo'], 0, 3) as $i => $p) {
            echo "Process $i:\n";
            foreach ($p as $k => $v) {
                echo "  $k: " . (is_scalar($v) ? $v : json_encode($v)) . "\n";
            }
        }
    }
    echo "\n=== serviceInfo (first 3) ===\n";
    if (isset($payload['serviceInfo']) && is_array($payload['serviceInfo'])) {
        foreach (array_slice($payload['serviceInfo'], 0, 3) as $i => $s) {
            echo "Service $i:\n";
            foreach ($s as $k => $v) {
                echo "  $k: " . (is_scalar($v) ? $v : json_encode($v)) . "\n";
            }
        }
    }
    echo "\n=== batteryInfo ===\n";
    if (isset($payload['batteryInfo'])) {
        foreach ($payload['batteryInfo'] as $k => $v) {
            echo "  $k: " . (is_scalar($v) ? $v : json_encode($v)) . "\n";
        }
    }
    echo "\n=== eventLogInfo (first 2) ===\n";
    if (isset($payload['eventLogInfo']) && is_array($payload['eventLogInfo'])) {
        foreach (array_slice($payload['eventLogInfo'], 0, 2) as $i => $e) {
            echo "Event $i:\n";
            foreach ($e as $k => $v) {
                echo "  $k: " . (is_scalar($v) ? $v : json_encode($v)) . "\n";
            }
        }
    }
}
