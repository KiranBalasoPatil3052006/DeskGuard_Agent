<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Machine;
use App\Models\RawPayloadLog;
use App\Services\PayloadProcessorService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AgentHealthController
 *
 * Receives the agent's HealthPayload (POST /api/v1/health) and maps it
 * to the internal telemetry format for processing.
 * The C# agent sends camelCase keys with "Info" suffixes
 * (e.g. cpuInfo, memoryInfo, antivirusInfo) which this controller
 * normalises to the format expected by the section processors.
 */
class AgentHealthController extends Controller
{
    use ApiResponseTrait;

    private PayloadProcessorService $payloadProcessorService;

    public function __construct(PayloadProcessorService $payloadProcessorService)
    {
        $this->payloadProcessorService = $payloadProcessorService;
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();

            $machineUid = $this->resolveMachineUid($request, $payload);
            if ($machineUid === '') {
                return $this->errorResponse('Machine identifier is required.', [], 422);
            }

            $companyId = $this->getOrCreateCompany();

            $machine = Machine::firstOrCreate(
                ['machine_uid' => $machineUid],
                [
                    'company_id'       => $companyId,
                    'hostname'         => $payload['systemInfo']['computerName'] ?? null,
                    'device_name'      => $payload['systemInfo']['computerName'] ?? null,
                    'operating_system' => $payload['systemInfo']['operatingSystem'] ?? null,
                    'is_online'        => true,
                    'is_active'        => true,
                    'last_heartbeat_at' => now(),
                ]
            );

            RawPayloadLog::create([
                'machine_id'  => $machine->id,
                'machine_uid' => $machineUid,
                'payload'     => json_encode($payload),
                'source_ip'   => $request->ip(),
                'received_at' => now(),
            ]);

            $normalised = $this->normalisePayload($payload, $machine);

            $this->payloadProcessorService->process($machine, $normalised);

            Log::info('AgentHealthController: Health payload processed', [
                'machine_id'  => $machine->id,
                'machine_uid' => $machineUid,
            ]);

            return $this->successResponse(null, 'Health data processed successfully.');
        } catch (Exception $e) {
            Log::error('AgentHealthController: Failed', [
                'machineId' => $request->input('machineId'),
                'error'     => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to process health data.',
                [],
                500
            );
        }
    }

    /**
     * Get the default company for agent-created machines.
     *
     * Uses AGENT_DEFAULT_COMPANY_ID from .env when set (dev/testing),
     * otherwise falls back to the first company in the database.
     */
    private function getOrCreateCompany(): int
    {
        $preferredId = (int) env('AGENT_DEFAULT_COMPANY_ID', 0);
        if ($preferredId > 0) {
            $company = Company::find($preferredId);
            if ($company) {
                return $company->id;
            }
        }

        $company = Company::first();
        if ($company) {
            return $company->id;
        }

        $company = Company::create([
            'name'      => 'Local Test Company',
            'is_active' => true,
        ]);

        return $company->id;
    }

    /**
     * Normalise the agent's HealthPayload format to the telemetry format
     * expected by the section processors.
     *
     * Agent sends:  cpuInfo, memoryInfo, diskInfo, etc.
     * Processors expect: cpu, memory, disks, etc.
     */
    private function normalisePayload(array $payload, Machine $machine): array
    {
        $systemInfo = $payload['systemInfo'] ?? [];

        $normalised = [
            'machineId'     => $payload['machineId'] ?? '',
            'computerName'  => $systemInfo['computerName'] ?? null,
            'collectedAt'   => $payload['timestamp'] ?? null,

            'systemInfo' => $systemInfo,

            'cpu'     => $payload['cpuInfo'] ?? null,
            'memory'  => $payload['memoryInfo'] ?? null,
            'disks'   => $payload['diskInfo'] ?? null,
            'battery' => $payload['batteryInfo'] ?? null,

            'networkAdapters' => $this->mapNetworkAdapters($payload['networkInfo'] ?? []),

            'processes' => $this->mapProcesses($payload['processInfo'] ?? []),
            'services'  => $payload['serviceInfo'] ?? null,

            'antivirus' => $this->mapAntivirus($payload['antivirusInfo'] ?? []),
            'firewall'  => $this->mapFirewall($payload['firewallInfo'] ?? []),

            'windowsUpdates' => $this->mapUpdates($payload['updateInfo'] ?? []),
            'eventLogs'      => $payload['eventLogInfo'] ?? null,

            // Previously null — now mapped from agent payload
            'loginActivities'   => $this->mapLoginActivities($payload['loginActivityInfo'] ?? []),
            'usbActivities'     => $this->mapUsbActivities($payload['usbActivityInfo'] ?? []),
            'startupPrograms'   => $payload['startupProgramInfo'] ?? null,
            'connectedDevices'  => $payload['peripheralInfo'] ?? null,
            'deviceEvents'      => $payload['deviceEventInfo'] ?? null,

            'hardwareInventory' => null,
            'softwareInventory' => null,
        ];

        // Update machine with employee mobile number if provided in payload
        $mobileNumber = $payload['employeeMobileNumber'] ?? $systemInfo['employeeMobileNumber'] ?? null;
        if ($mobileNumber && $machine) {
            $machine->update(['employee_mobile_number' => $mobileNumber]);
        }

        return $normalised;
    }

    private function resolveMachineUid(Request $request, array $payload): string
    {
        $candidates = [
            $payload['machineId'] ?? null,
            $payload['machine_uid'] ?? null,
            $payload['machineUid'] ?? null,
            $payload['agentId'] ?? null,
            $request->header('X-Agent-Id'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    private function mapNetworkAdapters(array $networkInfo): array
    {
        return array_map(function ($adapter) {
            return [
                'adapterName'         => $adapter['adapterName'] ?? 'Unknown',
                'isConnected'         => $adapter['isConnected'] ?? false,
                'ipAddress'           => $adapter['ipAddressV4'] ?? null,
                'macAddress'          => $adapter['macAddress'] ?? null,
                'connectionSpeedMbps' => $adapter['connectionSpeedMbps'] ?? null,
                'bytesSent'           => $adapter['bytesSent'] ?? null,
                'bytesReceived'       => $adapter['bytesReceived'] ?? null,
            ];
        }, is_array($networkInfo) ? $networkInfo : []);
    }

    private function mapProcesses(array $processInfo): array
    {
        return array_map(function ($proc) {
            return [
                'processName'        => $proc['processName'] ?? 'Unknown',
                'processId'          => $proc['processId'] ?? null,
                'cpuUsagePercentage' => $proc['cpuUsagePercentage'] ?? $proc['cpuUsage'] ?? null,
                'workingSetBytes'    => $proc['workingSetBytes'] ?? null,
                'memoryUsageMb'      => $proc['memoryUsageMb'] ?? null,
            ];
        }, is_array($processInfo) ? $processInfo : []);
    }

    private function mapLoginActivities(array $loginInfo): array
    {
        return array_map(function ($event) {
            $eventId = $event['eventId'] ?? null;
            $eventType = match ((int)($eventId ?? 0)) {
                4624 => 'login_success',
                4625 => 'login_failure',
                default => 'login_event',
            };

            return [
                'eventType' => $eventType,
                'username'  => $event['userName'] ?? $event['username'] ?? null,
                'sessionId' => $eventId !== null ? (string)$eventId : null,
                'eventTime' => $event['timeGenerated'] ?? $event['eventTime'] ?? null,
            ];
        }, is_array($loginInfo) ? $loginInfo : []);
    }

    private function mapUsbActivities(array $usbInfo): array
    {
        return array_map(function ($event) {
            $message = strtolower((string)($event['message'] ?? ''));
            $eventType = str_contains($message, 'remove') ||
                str_contains($message, 'disconnect')
                    ? 'removed'
                    : 'connected';

            return [
                'deviceName'   => $event['source'] ?? 'USB Device',
                'deviceSerial' => isset($event['eventId']) ? (string)$event['eventId'] : null,
                'eventType'    => $eventType,
                'eventTime'    => $event['timeGenerated'] ?? $event['eventTime'] ?? null,
            ];
        }, is_array($usbInfo) ? $usbInfo : []);
    }

    private function mapAntivirus(array $antivirusInfo): array
    {
        if (empty($antivirusInfo)) {
            return [];
        }

        return [
            'displayName'                => $antivirusInfo['displayName'] ?? null,
            'isRealTimeProtectionEnabled' => $antivirusInfo['isRealTimeProtectionEnabled'] ?? null,
            'isSignatureUpToDate'        => $antivirusInfo['isSignatureUpToDate'] ?? null,
            'productVersion'             => $antivirusInfo['productVersion'] ?? null,
        ];
    }

    private function mapFirewall(array $firewallInfo): array
    {
        if (empty($firewallInfo)) {
            return [];
        }

        return [
            'displayName'                 => 'Windows Firewall',
            'isDomainFirewallEnabled'     => $firewallInfo['isDomainFirewallEnabled'] ?? null,
            'isPrivateFirewallEnabled'    => $firewallInfo['isPrivateFirewallEnabled'] ?? null,
            'isPublicFirewallEnabled'     => $firewallInfo['isPublicFirewallEnabled'] ?? null,
            'activeProfile'               => $firewallInfo['activeProfile'] ?? null,
        ];
    }

    /**
     * Pass through the raw updateInfo summary. The UpdateProcessor
     * handles both summary format (with pendingUpdateCount etc.) and
     * array-of-updates format natively.
     */
    private function mapUpdates(array $updateInfo): array
    {
        if (empty($updateInfo)) {
            return [];
        }

        return $updateInfo;
    }
}
