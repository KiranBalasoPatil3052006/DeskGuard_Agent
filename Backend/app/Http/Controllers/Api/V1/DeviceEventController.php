<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceEvent;
use App\Models\Machine;
use App\Models\MachineConnectedDevice;
use App\Models\Alert;
use App\Models\AlertRule;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceEventController extends Controller
{
    use ApiResponseTrait;

    public function store(Request $request): JsonResponse
    {
        try {
            if (!$request->has('machine_uid') && ($request->has('machineId') || $request->has('machineUid'))) {
                $request->merge(['machine_uid' => $request->input('machineId', $request->input('machineUid'))]);
            }

            $validated = $request->validate([
                'machine_uid' => 'required|string|max:255',
                'device_name' => 'required|string|max:255',
                'device_type' => 'nullable|string|max:100',
                'manufacturer' => 'nullable|string|max:255',
                'connection_type' => 'nullable|string|max:50',
                'event_type' => 'required|string|in:Connected,Removed',
                'event_time' => 'required|date',
            ]);

            $machine = Machine::where('machine_uid', $validated['machine_uid'])->first();

            if (!$machine) {
                return $this->errorResponse('Machine not found.', [], 404);
            }

            $deviceEvent = DeviceEvent::create([
                'machine_id' => $machine->id,
                'device_name' => $validated['device_name'],
                'device_type' => $validated['device_type'] ?? null,
                'manufacturer' => $validated['manufacturer'] ?? null,
                'connection_type' => $validated['connection_type'] ?? null,
                'event_type' => $validated['event_type'],
                'event_time' => $validated['event_time'],
            ]);

            if ($validated['event_type'] === 'Connected') {
                MachineConnectedDevice::updateOrCreate(
                    [
                        'machine_id' => $machine->id,
                        'device_name' => $validated['device_name'],
                    ],
                    [
                        'device_type' => $validated['device_type'] ?? null,
                        'manufacturer' => $validated['manufacturer'] ?? null,
                        'connection_type' => $validated['connection_type'] ?? null,
                        'status' => 'connected',
                        'last_seen' => now(),
                    ]
                );

                $this->checkAlertRules($machine, $validated);
            } else {
                MachineConnectedDevice::where('machine_id', $machine->id)
                    ->where('device_name', $validated['device_name'])
                    ->update(['status' => 'disconnected', 'last_seen' => now()]);
            }

            return $this->createdResponse($deviceEvent, 'Device event recorded.');
        } catch (Exception $e) {
            Log::error('DeviceEventController::store failed', [
                'machine_uid' => $request->input('machine_uid'),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to record device event.', [], 500);
        }
    }

    public function sync(Request $request): JsonResponse
    {
        try {
            if (!$request->has('machine_uid') && ($request->has('machineId') || $request->has('machineUid'))) {
                $request->merge(['machine_uid' => $request->input('machineId', $request->input('machineUid'))]);
            }

            $validated = $request->validate([
                'machine_uid' => 'required|string|max:255',
                'devices' => 'required|array',
                'devices.*.device_name' => 'required|string|max:255',
                'devices.*.device_type' => 'nullable|string|max:100',
                'devices.*.manufacturer' => 'nullable|string|max:255',
                'devices.*.connection_type' => 'nullable|string|max:50',
                'devices.*.device_status' => 'nullable|string|max:50',
                'devices.*.last_seen' => 'nullable|date',
            ]);

            $machine = Machine::where('machine_uid', $validated['machine_uid'])->first();

            if (!$machine) {
                return $this->errorResponse('Machine not found.', [], 404);
            }

            MachineConnectedDevice::where('machine_id', $machine->id)
                ->where('status', 'connected')
                ->update(['status' => 'removed']);

            foreach ($validated['devices'] as $device) {
                MachineConnectedDevice::updateOrCreate(
                    [
                        'machine_id' => $machine->id,
                        'device_name' => $device['device_name'],
                    ],
                    [
                        'device_type' => $device['device_type'] ?? null,
                        'manufacturer' => $device['manufacturer'] ?? null,
                        'connection_type' => $device['connection_type'] ?? null,
                        'status' => $device['device_status'] ?? 'connected',
                        'last_seen' => $device['last_seen'] ?? now(),
                    ]
                );
            }

            return $this->successResponse(null, 'Device sync completed.');
        } catch (Exception $e) {
            Log::error('DeviceEventController::sync failed', [
                'machine_uid' => $request->input('machine_uid'),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Device sync failed.', [], 500);
        }
    }

    private function checkAlertRules(Machine $machine, array $data): void
    {
        $deviceType = strtolower($data['device_type'] ?? '');
        $deviceName = strtolower($data['device_name'] ?? '');

        $alertKeywords = ['usb', 'printer', 'external', 'bluetooth', 'scanner', 'webcam'];

        $matched = false;
        foreach ($alertKeywords as $keyword) {
            if (str_contains($deviceType, $keyword) || str_contains($deviceName, $keyword)) {
                $matched = true;
                break;
            }
        }

        if ($matched) {
            $rules = AlertRule::where(function ($q) use ($deviceType, $deviceName) {
                $q->where('rule_type', 'device_connected')
                    ->where(function ($sub) use ($deviceType, $deviceName) {
                        $sub->where('condition', 'LIKE', "%$deviceType%")
                            ->orWhere('condition', 'LIKE', "%$deviceName%");
                    });
            })->get();

            foreach ($rules as $rule) {
                Alert::create([
                    'machine_id' => $machine->id,
                    'alert_rule_id' => $rule->id,
                    'alert_type' => 'device_connected',
                    'severity' => $rule->severity ?? 'medium',
                    'title' => "Device connected: {$data['device_name']}",
                    'description' => "{$data['device_type']} device '{$data['device_name']}' connected.",
                    'triggered_at' => now(),
                    'is_acknowledged' => false,
                ]);
            }

            if ($rules->isEmpty()) {
                Alert::create([
                    'machine_id' => $machine->id,
                    'alert_rule_id' => null,
                    'alert_type' => 'device_connected',
                    'severity' => 'medium',
                    'title' => "Device connected: {$data['device_name']}",
                    'description' => "{$data['device_type']} device '{$data['device_name']}' connected.",
                    'triggered_at' => now(),
                    'is_acknowledged' => false,
                ]);
            }
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'machine_id' => 'nullable|integer|exists:machines,id',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = DeviceEvent::with('machine');

            if (!empty($validated['machine_id'])) {
                $query->where('machine_id', $validated['machine_id']);
            }

            $events = $query->orderBy('event_time', 'desc')
                ->paginate($validated['per_page'] ?? 50);

            return $this->successResponse($events, 'Device events retrieved.');
        } catch (Exception $e) {
            Log::error('DeviceEventController::index failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to retrieve device events.', [], 500);
        }
    }
}
