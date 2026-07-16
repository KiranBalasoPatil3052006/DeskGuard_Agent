<?php

/**
 * AgentChangeController
 *
 * Receives change detection events from the DeskGuard Agent.
 * The agent sends detected changes (hardware, software, security, network,
 * peripheral, configuration) to this endpoint as a batch payload.
 *
 * Flow:
 * 1. Agent detects changes via BaselineManager comparison
 * 2. Agent sends POST /api/v1/agent/changes with machine_uid + changes array
 * 3. This controller validates the payload, looks up the machine, and stores
 *    each change as a row in the change_history table
 * 4. For critical and important changes, alerts are generated so they appear
 *    on the dashboard and can trigger email notifications
 *
 * Expected payload format:
 * {
 *   "machine_uid": "DG-HASH-001",
 *   "changes": [{
 *     "category": "hardware",
 *     "change_type": "modified",
 *     "severity": "critical",
 *     "item_identifier": "RAM-SLOT-0",
 *     "item_label": "RAM Module 0",
 *     "previous_value": "...",
 *     "new_value": "...",
 *     "description": "...",
 *     "detected_at": "2026-07-14T08:15:00.000Z"
 *   }]
 * }
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\ChangeHistory;
use App\Models\Machine;
use App\Services\NotificationService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgentChangeController extends Controller
{
    use ApiResponseTrait;

    /** @var NotificationService For dispatching email alerts on critical changes. */
    private NotificationService $notificationService;

    /**
     * AgentChangeController constructor.
     *
     * @param NotificationService $notificationService Service for sending notifications.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the incoming change detection payload from the agent.
     * Validates the payload, resolves the machine, stores each change event,
     * and generates alerts for critical/important changes.
     *
     * @param Request $request The incoming HTTP request.
     * @return JsonResponse JSON response indicating success or failure.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $changes = $payload['changes'] ?? [];

            if (empty($changes)) {
                return $this->successResponse(null, 'No changes to process.');
            }

            $machineUid = $payload['machine_uid'] ?? '';
            if ($machineUid === '') {
                return $this->errorResponse('Machine identifier is required.', [], 422);
            }

            $machine = Machine::where('machine_uid', $machineUid)->first();
            if (!$machine) {
                return $this->errorResponse('Machine not found.', [], 404);
            }

            $created = 0;
            $alertCount = 0;

            foreach ($changes as $change) {
                // Store the change event in the change_history table.
                ChangeHistory::create([
                    'company_id' => $machine->company_id,
                    'machine_id' => $machine->id,
                    'category' => $change['category'] ?? 'unknown',
                    'change_type' => $change['change_type'] ?? 'unknown',
                    'severity' => $change['severity'] ?? 'information',
                    'item_identifier' => $change['item_identifier'] ?? null,
                    'item_label' => $change['item_label'] ?? null,
                    'previous_value' => $change['previous_value'] ?? null,
                    'new_value' => $change['new_value'] ?? null,
                    'description' => $change['description'] ?? null,
                    'metadata' => null,
                    'detected_at' => $change['detected_at'] ?? now(),
                ]);
                $created++;

                // Generate alerts for critical and important severity changes.
                $severity = $change['severity'] ?? 'information';
                if (in_array($severity, ['critical', 'important'])) {
                    $alert = $this->createChangeAlert(
                        $machine,
                        $severity,
                        $change['description'] ?? $change['item_label'] ?? 'Change detected',
                        $change
                    );
                    if ($alert !== null) {
                        $alertCount++;
                        // Send email notification for critical severity changes.
                        if ($severity === 'critical') {
                            $this->notificationService->sendEmailNotification($alert);
                        }
                    }
                }
            }

            Log::info('AgentChangeController: Changes stored', [
                'machine_id' => $machine->id,
                'machine_uid' => $machineUid,
                'count' => $created,
                'alerts_created' => $alertCount,
            ]);

            return $this->successResponse([
                'stored' => $created,
                'alerts' => $alertCount,
            ], 'Changes processed successfully.');
        } catch (Exception $e) {
            Log::error('AgentChangeController: Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to process changes.', [], 500);
        }
    }

    /**
     * Creates an alert for a critical or important change event.
     * Prevents duplicate alerts by checking for an existing open alert
     * with the same title for the same machine.
     *
     * @param Machine $machine The machine the change belongs to.
     * @param string $severity The severity level (critical or important).
     * @param string $description Human-readable description.
     * @param array $change The original change data for metadata.
     * @return Alert|null The created alert, or null if duplicate or failed.
     */
    private function createChangeAlert(Machine $machine, string $severity, string $description, array $change): ?Alert
    {
        try {
            $title = match (true) {
                $change['category'] === 'hardware' && $change['change_type'] === 'modified' => 'Hardware Change Detected',
                $change['category'] === 'security' && $change['change_type'] === 'disabled' => 'Security Feature Disabled',
                $change['category'] === 'software' && $change['change_type'] === 'removed' => 'Critical Software Removed',
                $change['category'] === 'network' && $change['change_type'] === 'modified' => 'Network Change Detected',
                $change['category'] === 'configuration' && $change['change_type'] === 'modified' => 'Configuration Change Detected',
                default => ucfirst($change['category'] ?? 'System') . ' Change Detected',
            };

            // Prevent duplicate alerts for the same type on the same machine.
            $existing = Alert::where('machine_id', $machine->id)
                ->where('title', $title)
                ->where('status', 'open')
                ->first();

            if ($existing) {
                return $existing;
            }

            $alert = Alert::create([
                'machine_id' => $machine->id,
                'company_id' => $machine->company_id,
                'severity' => $severity,
                'title' => $title,
                'description' => $description,
                'metadata' => json_encode([
                    'category' => $change['category'] ?? null,
                    'change_type' => $change['change_type'] ?? null,
                    'item_identifier' => $change['item_identifier'] ?? null,
                    'item_label' => $change['item_label'] ?? null,
                    'previous_value' => $change['previous_value'] ?? null,
                    'new_value' => $change['new_value'] ?? null,
                ]),
                'status' => 'open',
            ]);

            Log::info("AgentChangeController: Alert created - {$title}", [
                'machine_id' => $machine->id,
                'severity' => $severity,
            ]);

            return $alert;
        } catch (\Throwable $e) {
            Log::error('AgentChangeController::createChangeAlert - Failed', [
                'machine_id' => $machine->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
