<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RawPayloadLog;

$raw = RawPayloadLog::where('machine_uid', 'not like', 'TEST%')->latest()->first();
if (!$raw) {
    // Try any payload
    $raw = RawPayloadLog::latest()->first();
}

if ($raw) {
    $payload = json_decode($raw->payload, true);
    echo "machineId: " . ($payload['machineId'] ?? 'N/A') . "\n";
    echo "\n=== systemInfo keys ===\n";
    $si = $payload['systemInfo'] ?? [];
    if (!empty($si)) {
        foreach ($si as $k => $v) {
            echo "  $k: " . (is_string($v) ? $v : json_encode($v)) . "\n";
        }
    } else {
        echo "  (empty)\n";
    }
    
    echo "\n=== All top-level keys ===\n";
    foreach ($payload as $k => $v) {
        $type = gettype($v);
        $len = is_array($v) ? count($v) : (is_string($v) ? strlen($v) : 0);
        echo "  $k ($type" . ($len ? ", len=$len" : "") . ")\n";
    }
    
    if (isset($payload['memoryInfo']) && is_array($payload['memoryInfo'])) {
        echo "\n=== memoryInfo keys ===\n";
        foreach ($payload['memoryInfo'] as $k => $v) {
            echo "  $k: " . (is_scalar($v) ? $v : json_encode($v)) . "\n";
        }
    }
} else {
    echo "No raw payloads found\n";
}
