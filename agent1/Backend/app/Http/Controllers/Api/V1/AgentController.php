<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\HealthDataDTO;
use App\DTOs\InventoryDataDTO;
use App\DTOs\MachineRegistrationDTO;
use App\DTOs\SecurityDataDTO;
use App\Http\Controllers\Controller;
use App\Services\AgentRegistrationService;
use App\Services\InventoryService;
use App\Services\MachineService;
use App\Services\MonitoringService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AgentController
 *
 * Handles all agent-to-server communication including registration,
 * heartbeat, health metrics, inventory sync, and security data.
 * Delegates all business logic to respective services.
 *
 * @package App\Http\Controllers\Api\V1
 */
class AgentController extends Controller
{
    use ApiResponseTrait;

    private AgentRegistrationService $agentRegistrationService;
    private MachineService $machineService;
    private MonitoringService $monitoringService;
    private InventoryService $inventoryService;

    /**
     * AgentController constructor.
     *
     * @param AgentRegistrationService $agentRegistrationService
     * @param MachineService           $machineService
     * @param MonitoringService        $monitoringService
     * @param InventoryService         $inventoryService
     */
    public function __construct(
        AgentRegistrationService $agentRegistrationService,
        MachineService $machineService,
        MonitoringService $monitoringService,
        InventoryService $inventoryService
    ) {
        $this->agentRegistrationService = $agentRegistrationService;
        $this->machineService = $machineService;
        $this->monitoringService = $monitoringService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Register a new machine agent.
     *
     * Validates the machine UID and activation token, then delegates
     * registration to AgentRegistrationService.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'machine_uid'      => 'required|string|max:255',
                'activation_token' => 'required|string|max:255',
                'hostname'         => 'nullable|string|max:255',
                'operating_system' => 'nullable|string|max:255',
            ]);

            $dto = new MachineRegistrationDTO($validated);

            $machine = $this->agentRegistrationService->register($dto);

            return $this->createdResponse($machine, 'Machine registered successfully.');
        } catch (Exception $e) {
            Log::error('AgentController::register - Registration failed', [
                'machine_uid' => $request->input('machine_uid'),
                'error'       => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Machine registration failed.',
                method_exists($e, 'getErrors') ? $e->getErrors() : [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Receive a heartbeat from an agent.
     *
     * Validates the machine token and updates the last heartbeat timestamp.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function heartbeat(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'machine_uid' => 'required|string|max:255',
            ]);

            $this->machineService->updateHeartbeat($validated['machine_uid']);

            return $this->successResponse(null, 'Heartbeat received.');
        } catch (Exception $e) {
            Log::error('AgentController::heartbeat - Heartbeat processing failed', [
                'machine_uid' => $request->input('machine_uid'),
                'error'       => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to process heartbeat.', [], 500);
        }
    }

    /**
     * Receive health monitoring data from an agent.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function health(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'machine_uid'           => 'required|string|max:255',
                'cpu_percentage'        => 'nullable|numeric|between:0,100',
                'cpu_temperature'       => 'nullable|numeric',
                'cpu_clock_speed'       => 'nullable|numeric',
                'cpu_core_count'        => 'nullable|integer|min:1',
                'ram_total_bytes'       => 'nullable|integer|min:0',
                'ram_used_bytes'        => 'nullable|integer|min:0',
                'ram_available_bytes'   => 'nullable|integer|min:0',
                'disk_total_bytes'      => 'nullable|integer|min:0',
                'disk_used_bytes'       => 'nullable|integer|min:0',
                'disk_free_bytes'       => 'nullable|integer|min:0',
                'battery_percentage'    => 'nullable|numeric|between:0,100',
                'battery_charging_status' => 'nullable|boolean',
                'network_received_bytes' => 'nullable|integer|min:0',
                'network_sent_bytes'    => 'nullable|integer|min:0',
                'online_status'         => 'sometimes|boolean',
            ]);

            $dto = new HealthDataDTO($validated);

            $this->monitoringService->processHealthData($dto);

            return $this->successResponse(null, 'Health data processed successfully.');
        } catch (Exception $e) {
            Log::error('AgentController::health - Health data processing failed', [
                'machine_uid' => $request->input('machine_uid'),
                'error'       => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to process health data.',
                method_exists($e, 'getErrors') ? $e->getErrors() : [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Receive inventory data from an agent.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function inventory(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'machine_uid' => 'required|string|max:255',
                'hardware'    => 'sometimes|array',
                'hardware.*.hardware_type' => 'nullable|string',
                'hardware.*.manufacturer'  => 'nullable|string',
                'hardware.*.model'         => 'nullable|string',
                'hardware.*.serial_number' => 'nullable|string',
                'hardware.*.revision'      => 'nullable|string',
                'software'    => 'sometimes|array',
                'software.*.software_name' => 'nullable|string',
                'software.*.version'       => 'nullable|string',
                'software.*.publisher'     => 'nullable|string',
                'software.*.install_date'  => 'nullable|string',
            ]);

            $dto = new InventoryDataDTO($validated);

            $this->inventoryService->processHardwareInventory($dto);
            $this->inventoryService->processSoftwareInventory($dto);

            return $this->successResponse(null, 'Inventory data processed successfully.');
        } catch (Exception $e) {
            Log::error('AgentController::inventory - Inventory data processing failed', [
                'machine_uid' => $request->input('machine_uid'),
                'error'       => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to process inventory data.',
                method_exists($e, 'getErrors') ? $e->getErrors() : [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Receive security data from an agent.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function security(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'machine_uid'          => 'required|string|max:255',
                'antivirus'            => 'sometimes|array',
                'antivirus.product_name'          => 'nullable|string',
                'antivirus.is_enabled'            => 'nullable|boolean',
                'antivirus.is_updated'            => 'nullable|boolean',
                'antivirus.real_time_protection'  => 'nullable|boolean',
                'antivirus.definition_version'    => 'nullable|string',
                'antivirus.engine_version'        => 'nullable|string',
                'firewall'             => 'sometimes|array',
                'firewall.display_name' => 'nullable|string',
                'firewall.is_enabled'  => 'nullable|boolean',
                'firewall.profile'     => 'nullable|string',
                'login_activities'     => 'sometimes|array',
                'login_activities.*.username'    => 'nullable|string',
                'login_activities.*.session_type' => 'nullable|string',
                'login_activities.*.logon_id'    => 'nullable|string',
                'login_activities.*.logon_time'  => 'nullable|string',
                'login_activities.*.logoff_time' => 'nullable|string',
                'usb_activities'       => 'sometimes|array',
                'usb_activities.*.device_name'   => 'nullable|string',
                'usb_activities.*.device_serial' => 'nullable|string',
                'usb_activities.*.vendor_id'     => 'nullable|string',
                'usb_activities.*.product_id'    => 'nullable|string',
                'usb_activities.*.drive_letter'  => 'nullable|string',
                'usb_activities.*.action'        => 'nullable|string',
            ]);

            $dto = new SecurityDataDTO($validated);

            $this->inventoryService->processSecurityData($dto);

            return $this->successResponse(null, 'Security data processed successfully.');
        } catch (Exception $e) {
            Log::error('AgentController::security - Security data processing failed', [
                'machine_uid' => $request->input('machine_uid'),
                'error'       => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to process security data.',
                method_exists($e, 'getErrors') ? $e->getErrors() : [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }
}
