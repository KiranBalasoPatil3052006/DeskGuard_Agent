<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\User;
use App\Models\Machine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

// Create test company if not exists
$company = Company::firstOrCreate(
    ['email' => 'test@deskguard.local'],
    ['name' => 'Test Company', 'is_active' => true]
);
echo "Company: {$company->id} {$company->name}\n";

// Create a test admin user if not exists
$user = User::firstOrCreate(
    ['email' => 'admin@deskguard.local'],
    [
        'company_id' => $company->id,
        'name' => 'Test Admin',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]
);
echo "User: {$user->id} {$user->name}\n";

// Generate a unique machine UID
$machineUid = 'TEST-' . strtoupper(substr(md5(uniqid()), 0, 8));

// Create the machine record directly
$machine = Machine::create([
    'company_id' => $company->id,
    'machine_uid' => $machineUid,
    'hostname' => 'DESKTOP-TEST-' . substr($machineUid, -4),
    'device_name' => 'Test Workstation',
    'operating_system' => 'Windows 11 Pro',
    'os_version' => '24H2',
    'manufacturer' => 'Dell Inc.',
    'model' => 'Precision 7780',
    'serial_number' => 'SN-' . strtoupper(substr(md5($machineUid), 0, 10)),
    'bios_version' => '1.24.0',
    'processor' => 'Intel(R) Core(TM) i7-14700HX',
    'ram_gb' => 32,
    'is_online' => false,
    'employee_mobile_number' => '+918765432109',
    'domain_name' => 'WORKGROUP',
    'architecture' => 'x64',
    'uptime_seconds' => 86400,
    'current_logged_in_users' => 'TestUser',
    'status' => 'active',
    'is_active' => true,
]);
echo "Machine created:\n";
echo "  ID: {$machine->id}\n";
echo "  UID: {$machineUid}\n";
echo "  Hostname: {$machine->hostname}\n";
echo "  Mobile: {$machine->employee_mobile_number}\n\n";

// Now send a full health payload via the API
$url = 'http://127.0.0.1:8000/api/v1/health';

$payload = [
    'machineId' => $machineUid,
    'employeeMobileNumber' => '+9187654321',
    'hostname' => 'DESKTOP-TEST-' . substr($machineUid, -4),
    'deviceName' => 'Test Workstation',
    'domainName' => 'WORKGROUP',
    'architecture' => 'x64',
    'operatingSystem' => 'Windows 11 Pro',
    'osVersion' => '24H2',
    'uptimeSeconds' => 86400,
    'currentLoggedInUsers' => 'TestUser',
    'manufacturer' => 'Dell Inc.',
    'model' => 'Precision 7780',
    'serialNumber' => 'SN' . strtoupper(substr(md5($machineUid), 0, 8)),
    'biosVersion' => '1.24.0',
    'processor' => 'Intel(R) Core(TM) i7-13700HX',
    'ramGb' => 32,
    'cpuInfo' => [
        'cpuUsage' => 45.2,
        'cpuTemperature' => 62.5,
        'clockSpeedMhz' => 2400,
        'coreCount' => 16,
    ],
    'memoryInfo' => [
        'totalBytes' => 34359738368,
        'usedBytes' => 17179869184,
        'availableBytes' => 17179869184,
        'usagePercent' => 50.0,
    ],
    'diskInfo' => [
        'totalBytes' => 512110190592,
        'usedBytes' => 256055095296,
        'freeBytes' => 256055095296,
        'usagePercent' => 50.0,
    ],
    'networkInfo' => [
        'bytesSent' => 1048576,
        'bytesReceived' => 2097152,
    ],
    'batteryInfo' => [
        'isPresent' => true,
        'percentage' => 85.0,
        'chargingStatus' => false,
        'wearLevel' => 5.2,
        'designCapacity' => 60000,
        'fullChargeCapacity' => 55000,
    ],
    'antivirusInfo' => [
        'displayName' => 'Windows Defender',
        'isEnabled' => true,
        'isUpdated' => true,
        'realTimeProtection' => true,
        'definitionStatus' => 'Up to date',
    ],
    'firewallInfo' => [
        'isEnabled' => true,
        'profileName' => 'Domain',
    ],
    'updateInfo' => [
        'pendingUpdateCount' => 3,
        'updates' => [
            [
                'title' => 'KB5050001 Security Update',
                'description' => 'Security Update for Windows',
                'severity' => 'Critical',
                'category' => 'Security',
                'kbId' => 'KB5050001',
                'isInstalled' => false,
            ],
            [
                'title' => 'KB5050002 Cumulative Update',
                'description' => 'Cumulative Update for Windows 11',
                'severity' => 'Important',
                'category' => 'Update',
                'kbId' => 'KB5050002',
                'isInstalled' => false,
            ],
        ],
    ],
    'loginActivityInfo' => [
        [
            'eventType' => 'Logon',
            'username' => 'TestUser',
            'sessionId' => 'S-1-5-21-12345-67890',
            'logonTime' => date('c', strtotime('-2 hours')),
        ],
    ],
    'processInfo' => [
        ['processName' => 'chrome.exe', 'processId' => 1234, 'executablePath' => 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', 'threadCount' => 45, 'userName' => 'TestUser', 'cpuUsage' => 12.5, 'memoryUsage' => 450.0],
        ['processName' => 'explorer.exe', 'processId' => 5678, 'executablePath' => 'C:\\Windows\\explorer.exe', 'threadCount' => 30, 'userName' => 'TestUser', 'cpuUsage' => 2.1, 'memoryUsage' => 120.0],
        ['processName' => 'outlook.exe', 'processId' => 9012, 'executablePath' => 'C:\\Program Files\\Microsoft Office\\root\\Office16\\OUTLOOK.EXE', 'threadCount' => 22, 'userName' => 'TestUser', 'cpuUsage' => 5.3, 'memoryUsage' => 280.0],
    ],
    'serviceInfo' => [
        ['serviceName' => 'Spooler', 'displayName' => 'Print Spooler', 'status' => 'Running', 'startType' => 'Automatic'],
        ['serviceName' => 'wuauserv', 'displayName' => 'Windows Update', 'status' => 'Running', 'startType' => 'Automatic'],
        ['serviceName' => 'MpsSvc', 'displayName' => 'Windows Firewall', 'status' => 'Running', 'startType' => 'Automatic'],
    ],
    'startupProgramInfo' => [
        ['programName' => 'OneDrive', 'programPath' => 'C:\\Users\\TestUser\\AppData\\Local\\Microsoft\\OneDrive\\OneDrive.exe', 'startupType' => 'CurrentUser'],
        ['programName' => 'Spotify', 'programPath' => 'C:\\Users\\TestUser\\AppData\\Roaming\\Spotify\\Spotify.exe', 'startupType' => 'CurrentUser'],
    ],
    'eventLogInfo' => [
        ['logName' => 'System', 'eventId' => 1001, 'level' => 'Error', 'source' => 'EventLog', 'message' => 'The system has rebooted without cleanly shutting down first.', 'timeGenerated' => date('c', strtotime('-30 minutes'))],
        ['logName' => 'Application', 'eventId' => 0, 'level' => 'Warning', 'source' => 'Application Error', 'message' => 'Faulting application: notepad.exe', 'timeGenerated' => date('c', strtotime('-1 hour'))],
    ],
];

echo "Sending health payload to $url...\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: " . substr($response, 0, 500) . "\n\n";

if ($httpCode === 200) {
    echo "SUCCESS: Health payload processed!\n";
    echo "\nNow run: php _verify_final.php to check all data\n";
} else {
    echo "FAILED: Health payload returned $httpCode\n";
    echo "Make sure the Laravel server is running: php artisan serve\n";
}

// Write the machine UID to a temp file for verification scripts
file_put_contents(__DIR__ . '/_last_machine_uid.txt', $machineUid);
echo "\nMachine UID saved to _last_machine_uid.txt\n";