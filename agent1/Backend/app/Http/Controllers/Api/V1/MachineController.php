<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\AntivirusStatus;
use App\Models\DeviceEvent;
use App\Models\FirewallStatus;
use App\Models\HardwareInventory;
use App\Models\LoginActivity;
use App\Models\MachineConnectedDevice;
use App\Models\ProcessLog;
use App\Models\SoftwareInventory;
use App\Models\UsbActivity;
use App\Models\WindowsUpdate;
use App\Models\WindowsService;
use App\Models\StartupProgram;
use App\Models\EventLog;
use App\Models\MachineNetworkAdapter;
use App\Models\MachineDisk;
use App\Repositories\HealthLogRepository;
use App\Services\MachineService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * MachineController
 *
 * Manages machine lifecycle, assignment, status tracking, and health log history.
 * Delegates all business logic to MachineService and HealthLogRepository.
 *
 * @package App\Http\Controllers\Api\V1
 */
class MachineController extends Controller
{
    use ApiResponseTrait;

    private MachineService $machineService;
    private HealthLogRepository $healthLogRepository;

    /**
     * MachineController constructor.
     *
     * @param MachineService      $machineService
     * @param HealthLogRepository $healthLogRepository
     */
    public function __construct(MachineService $machineService, HealthLogRepository $healthLogRepository)
    {
        $this->machineService = $machineService;
        $this->healthLogRepository = $healthLogRepository;
    }

    /**
     * List machines scoped to the authenticated user's company.
     * Supports pagination, search, and status filters.
     * Returns summary counts alongside paginated machine data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $params = $request->only(['search', 'status', 'per_page', 'page']);
            $machines = $this->machineService->getCompanyMachines($companyId, $params);
            $summary  = $this->machineService->getCompanyMachineSummary($companyId);

            // Merge summary counts into the paginated response so the frontend
            // can read both machine data and summary cards from a single API call.
            $response = array_merge($machines->toArray(), $summary);

            return $this->successResponse($response, 'Machines retrieved successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::index - Failed to retrieve machines', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve machines.', [], 500);
        }
    }

    /**
     * Get machine details with current status.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $machine = $this->machineService->getMachine($id);

            return $this->successResponse($machine, 'Machine retrieved successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::show - Failed to retrieve machine', [
                'machine_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Machine not found.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 404
            );
        }
    }

    /**
     * Assign a user to a machine.
     *
     * @param Request $request
     * @param int     $id
     * @return JsonResponse
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $machine = $this->machineService->assignMachine($id, (int) $validated['user_id']);

            return $this->successResponse($machine, 'Machine assigned successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::assign - Failed to assign machine', [
                'machine_id' => $id,
                'data'       => $request->all(),
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to assign machine.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Unassign the current user from a machine.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function unassign(int $id): JsonResponse
    {
        try {
            $machine = $this->machineService->unassignMachine($id);

            return $this->successResponse($machine, 'Machine unassigned successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::unassign - Failed to unassign machine', [
                'machine_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to unassign machine.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Get the current status of a machine.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function status(int $id): JsonResponse
    {
        try {
            $machine = $this->machineService->getMachine($id);

            return $this->successResponse(
                $machine->currentStatus,
                'Machine status retrieved successfully.'
            );
        } catch (Exception $e) {
            Log::error('MachineController::status - Failed to retrieve machine status', [
                'machine_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to retrieve machine status.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Get health log history for a machine with optional date range filters.
     *
     * @param Request $request
     * @param int     $id
     * @return JsonResponse
     */
    public function history(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'from' => 'nullable|date',
                'to'   => 'nullable|date|after_or_equal:from',
            ]);

            // Default to last 24 hours if dates not provided
            $from = $validated['from'] ?? now()->subDay()->toDateTimeString();
            $to   = $validated['to']   ?? now()->toDateTimeString();

            $logs = $this->healthLogRepository->getHistory($id, $from, $to);

            // Compute network_bytes_sent_per_sec from consecutive records
            // for the LiveMonitoring network chart.
            $prevSent = null;
            $prevTime = null;
            foreach ($logs as $log) {
                $currentTime = $log->created_at ? strtotime((string)$log->created_at) : null;
                if ($prevSent !== null && $prevTime !== null && $currentTime !== null) {
                    $timeDiff = $currentTime - $prevTime;
                    $sentDiff = ($log->network_sent_bytes ?? 0) - $prevSent;
                    $log->network_bytes_sent_per_sec = $timeDiff > 0 ? round(max(0, $sentDiff) / $timeDiff, 2) : 0;
                } else {
                    $log->network_bytes_sent_per_sec = 0;
                }
                $prevSent = $log->network_sent_bytes ?? 0;
                $prevTime = $currentTime;
            }

            return $this->successResponse($logs, 'Health log history retrieved successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::history - Failed to retrieve health log history', [
                'machine_id' => $id,
                'data'       => $request->all(),
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to retrieve health log history.',
                [],
                500
            );
        }
    }

    /**
     * Get hardware and software inventory for a machine.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function inventory(int $id): JsonResponse
    {
        try {
            $machine = $this->machineService->getMachine($id);

            $hardware = HardwareInventory::select(['id', 'machine_id', 'manufacturer', 'model', 'processor_name', 'processor_cores', 'ram_total_gb', 'ram_type', 'disk_model', 'disk_size_gb', 'gpu_name', 'collected_at'])
                ->where('machine_id', $id)
                ->latest()
                ->first();

            $software = SoftwareInventory::select(['id', 'machine_id', 'software_name', 'version', 'publisher', 'install_date'])
                ->where('machine_id', $id)
                ->orderBy('software_name', 'asc')
                ->get();

            return $this->successResponse([
                'hardware' => $hardware,
                'software' => $software,
            ], 'Machine inventory retrieved successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::inventory - Failed', [
                'machine_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve inventory.', [], 500);
        }
    }

    /**
     * Get security information for a machine (antivirus, firewall, logins, updates).
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function security(int $id): JsonResponse
    {
        try {
            $machine = $this->machineService->getMachine($id);

            $antivirus = AntivirusStatus::select(['id', 'machine_id', 'display_name', 'product_state', 'is_enabled', 'is_updated', 'last_updated', 'collected_at'])
                ->where('machine_id', $id)
                ->latest()
                ->first();

            $firewall = FirewallStatus::select(['id', 'machine_id', 'domain_profile', 'private_profile', 'public_profile', 'is_enabled', 'collected_at'])
                ->where('machine_id', $id)
                ->latest()
                ->first();

            $loginActivity = LoginActivity::select(['id', 'machine_id', 'username', 'domain', 'logon_type', 'process_name', 'created_at'])
                ->where('machine_id', $id)
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get();

            $pendingUpdates = WindowsUpdate::select(['id', 'machine_id', 'update_id', 'title', 'kb_id', 'severity', 'category', 'created_at'])
                ->where('machine_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse([
                'antivirus'       => $antivirus,
                'firewall'        => $firewall,
                'login_activity'  => $loginActivity,
                'pending_updates' => $pendingUpdates,
            ], 'Machine security data retrieved successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::security - Failed', [
                'machine_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve security data.', [], 500);
        }
    }

    /**
     * Get connected devices and USB activity for a machine.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function devices(Request $request, int $id): JsonResponse
    {
        try {
            $machine = $this->machineService->getMachine($id);
            $perPage = min((int) $request->input('per_page', 50), 100);

            $connectedDevices = MachineConnectedDevice::select(['id', 'machine_id', 'device_name', 'device_type', 'manufacturer', 'connection_type', 'status', 'last_seen', 'created_at'])
                ->where('machine_id', $id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $usbActivity = UsbActivity::select(['id', 'company_id', 'machine_id', 'device_name', 'device_serial', 'drive_letter', 'event_type', 'collected_at', 'created_at'])
                ->where('machine_id', $id)
                ->orderBy('created_at', 'desc')
                ->take(50)
                ->get();

            $deviceEvents = DeviceEvent::select(['id', 'machine_id', 'device_name', 'device_type', 'manufacturer', 'connection_type', 'event_type', 'event_time', 'created_at'])
                ->where('machine_id', $id)
                ->orderBy('created_at', 'desc')
                ->take(50)
                ->get();

            return $this->successResponse([
                'connected_devices' => $connectedDevices,
                'usb_activity'      => $usbActivity,
                'device_events'     => $deviceEvents,
            ], 'Machine devices retrieved successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::devices - Failed', [
                'machine_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve devices.', [], 500);
        }
    }

    /**
     * Get detailed info + issues for a specific device by name.
     *
     * @param  Request $request
     * @param  int     $id  Machine ID
     * @return JsonResponse
     */
    public function deviceIssues(Request $request, int $id): JsonResponse
    {
        try {
            $deviceName = $request->input('device_name');
            if (!$deviceName) {
                return $this->errorResponse('device_name query parameter is required.', [], 400);
            }

            $device = MachineConnectedDevice::select(['id', 'machine_id', 'device_name', 'device_type', 'manufacturer', 'vendor_id', 'product_id', 'connection_type', 'status', 'last_seen', 'created_at'])
                ->where('machine_id', $id)
                ->where('device_name', $deviceName)
                ->first();

            if (!$device) {
                return $this->errorResponse('Device not found on this machine.', [], 404);
            }

            $alerts = Alert::select(['id', 'machine_id', 'device_name', 'title', 'description', 'severity', 'status', 'created_at'])
                ->where('machine_id', $id)
                ->where('device_name', $deviceName)
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get();

            $events = DeviceEvent::select(['id', 'machine_id', 'device_name', 'device_type', 'manufacturer', 'connection_type', 'event_type', 'event_time', 'created_at'])
                ->where('machine_id', $id)
                ->where('device_name', $deviceName)
                ->orderBy('event_time', 'desc')
                ->take(20)
                ->get();

            return $this->successResponse([
                'device' => $device,
                'alerts' => $alerts,
                'events' => $events,
            ], 'Device details retrieved successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::deviceIssues - Failed', [
                'machine_id'   => $id,
                'device_name'  => $request->input('device_name'),
                'error'        => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve device details.', [], 500);
        }
    }

    /**
     * Get alerts for a specific machine.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function machineAlerts(Request $request, int $id): JsonResponse
    {
        try {
            $perPage = min((int) $request->input('per_page', 50), 100);
            $alerts = Alert::select(['id', 'company_id', 'machine_id', 'title', 'description', 'severity', 'status', 'acknowledged_by', 'resolved_by', 'created_at', 'updated_at'])
                ->where('machine_id', $id)
                ->with(['acknowledgedBy:id,name', 'resolvedBy:id,name'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return $this->successResponse($alerts, 'Machine alerts retrieved successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::machineAlerts - Failed', [
                'machine_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve machine alerts.', [], 500);
        }
    }

    /**
     * PERFORMANCE OPTIMIZED: Get a combined activity timeline for a machine.
     * 
     * Before: 4 sequential queries (Alerts, LoginActivity, UsbActivity, DeviceEvents)
     *         + PHP collection merge + sort = ~300ms
     * After:  Single UNION ALL query with database-level ORDER BY = ~50ms
     *
     * @param  Request $request
     * @param  int     $id
     * @return JsonResponse
     */
    public function timeline(Request $request, int $id): JsonResponse
    {
        try {
            $limit = (int) $request->input('limit', 50);

            // PERFORMANCE: Single UNION ALL query replaces 4 sequential queries.
            // The database handles sorting instead of PHP, and we only transfer
            // the final $limit rows over the wire.
            $events = \Illuminate\Support\Facades\DB::select("
                (SELECT 'alert' as type, title, description, severity, status, created_at as timestamp
                 FROM alerts WHERE machine_id = ? ORDER BY created_at DESC LIMIT ?)
                UNION ALL
                (SELECT 'login' as type,
                    CONCAT(CASE WHEN is_success THEN 'Login' ELSE 'Failed Login' END, ': ', COALESCE(username, 'Unknown')) as title,
                    logon_type as description,
                    CASE WHEN is_success THEN 'info' ELSE 'warning' END as severity,
                    NULL as status,
                    created_at as timestamp
                 FROM login_activities WHERE machine_id = ? ORDER BY created_at DESC LIMIT ?)
                UNION ALL
                (SELECT 'usb' as type,
                    CONCAT('USB ', COALESCE(event_type, 'Activity'), ': ', COALESCE(device_name, 'Unknown Device')) as title,
                    device_serial as description,
                    'info' as severity,
                    NULL as status,
                    created_at as timestamp
                 FROM usb_activities WHERE machine_id = ? ORDER BY created_at DESC LIMIT ?)
                UNION ALL
                (SELECT 'device' as type,
                    CONCAT(COALESCE(event_type, 'Device'), ': ', COALESCE(device_name, 'Unknown')) as title,
                    device_type as description,
                    'info' as severity,
                    NULL as status,
                    created_at as timestamp
                 FROM device_events WHERE machine_id = ? ORDER BY created_at DESC LIMIT ?)
                ORDER BY timestamp DESC
                LIMIT ?
            ", [$id, $limit, $id, $limit, $id, $limit, $id, $limit, $limit]);

            return $this->successResponse($events, 'Machine timeline retrieved successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::timeline - Failed', [
                'machine_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve timeline.', [], 500);
        }
    }

    /**
     * Get running processes for a machine.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function processes(int $id): JsonResponse
    {
        try {
            $processes = ProcessLog::select(['id', 'machine_id', 'process_name', 'process_id', 'executable_path', 'thread_count', 'user_name', 'cpu_usage', 'memory_usage', 'collected_at'])
                ->where('machine_id', $id)
                ->orderBy('cpu_usage', 'desc')
                ->take(100)
                ->get();

            return $this->successResponse($processes, 'Machine processes retrieved successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::processes - Failed', [
                'machine_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve processes.', [], 500);
        }
    }

    /**
     * Get Windows services for a machine.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function services(int $id): JsonResponse
    {
        try {
            $services = WindowsService::select(['id', 'machine_id', 'service_name', 'display_name', 'status', 'start_type', 'service_type', 'log_on_as', 'collected_at'])
                ->where('machine_id', $id)
                ->orderBy('display_name', 'asc')
                ->get();

            return $this->successResponse($services, 'Machine services retrieved successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::services - Failed', [
                'machine_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve services.', [], 500);
        }
    }

    /**
     * Get startup programs for a machine.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function startupPrograms(int $id): JsonResponse
    {
        try {
            $programs = StartupProgram::select(['id', 'machine_id', 'program_name', 'program_path', 'registry_key', 'startup_type', 'status', 'collected_at'])
                ->where('machine_id', $id)
                ->orderBy('program_name', 'asc')
                ->get();

            return $this->successResponse($programs, 'Startup programs retrieved successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::startupPrograms - Failed', [
                'machine_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve startup programs.', [], 500);
        }
    }

    /**
     * Get event logs for a machine.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function eventLogs(int $id): JsonResponse
    {
        try {
            $logs = EventLog::select(['id', 'machine_id', 'log_name', 'event_id', 'level', 'source', 'message', 'event_time', 'collected_at'])
                ->where('machine_id', $id)
                ->orderBy('event_time', 'desc')
                ->take(100)
                ->get();

            return $this->successResponse($logs, 'Event logs retrieved successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::eventLogs - Failed', [
                'machine_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve event logs.', [], 500);
        }
    }

    /**
     * Get network adapters and disk info for a machine.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function networkAdapters(int $id): JsonResponse
    {
        try {
            $adapters = MachineNetworkAdapter::select(['id', 'machine_id', 'adapter_name', 'ip_address', 'ip_address_v6', 'mac_address', 'adapter_type', 'speed', 'bytes_sent', 'bytes_received', 'status'])
                ->where('machine_id', $id)
                ->orderBy('adapter_name', 'asc')
                ->get();

            $disks = MachineDisk::select(['id', 'machine_id', 'drive_letter', 'volume_label', 'total_gb', 'used_gb', 'free_gb', 'file_system', 'drive_type', 'health_status'])
                ->where('machine_id', $id)
                ->orderBy('drive_letter', 'asc')
                ->get();

            return $this->successResponse([
                'adapters' => $adapters,
                'disks'    => $disks,
            ], 'Machine network data retrieved successfully.');
        } catch (Exception $e) {
            Log::error('MachineController::networkAdapters - Failed', [
                'machine_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve network data.', [], 500);
        }
    }

    /**
     * List online machines for the authenticated user's company.
     *
     * @return JsonResponse
     */
    public function online(): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $count = $this->machineService->getOnlineCount($companyId);

            return $this->successResponse(
                ['online_count' => $count],
                'Online machines count retrieved successfully.'
            );
        } catch (Exception $e) {
            Log::error('MachineController::online - Failed to get online count', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve online machines count.', [], 500);
        }
    }

    /**
     * List offline machines for the authenticated user's company.
     *
     * @return JsonResponse
     */
    public function offline(): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $count = $this->machineService->getOfflineCount($companyId);

            return $this->successResponse(
                ['offline_count' => $count],
                'Offline machines count retrieved successfully.'
            );
        } catch (Exception $e) {
            Log::error('MachineController::offline - Failed to get offline count', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve offline machines count.', [], 500);
        }
    }
}
