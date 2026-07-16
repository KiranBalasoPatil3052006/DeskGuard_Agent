<?php

/**
 * BaselineController
 *
 * Manages approved baselines for machines across all categories:
 * hardware, software, security, and configuration.
 *
 * Provides endpoints for:
 * - Viewing baselines per category per machine
 * - Resyncing baselines when changes are approved
 * - Approving individual changes (updates baseline + resolves alert)
 *
 * Baselines represent the "known good state" and are used by the
 * agent-side comparison engine to detect unauthorized changes.
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\ChangeHistory;
use App\Models\ConfigurationBaseline;
use App\Models\HardwareBaseline;
use App\Models\Machine;
use App\Models\SecurityBaseline;
use App\Models\SoftwareBaseline;
use App\Services\BaselineService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BaselineController extends Controller
{
    use ApiResponseTrait;

    private BaselineService $baselineService;

    public function __construct(BaselineService $baselineService)
    {
        $this->baselineService = $baselineService;
    }

    /**
     * Get the hardware baseline for a machine.
     *
     * @param int $machineId The machine ID.
     * @return JsonResponse
     */
    public function hardwareBaseline(int $machineId): JsonResponse
    {
        $user = Auth::user();
        $machine = Machine::where('company_id', $user->company_id)->findOrFail($machineId);

        $baseline = HardwareBaseline::where('machine_id', $machine->id)
            ->orderBy('component')
            ->get();

        return $this->successResponse($baseline, 'Hardware baseline retrieved successfully');
    }

    /**
     * Get the software baseline for a machine.
     *
     * @param int $machineId The machine ID.
     * @return JsonResponse
     */
    public function softwareBaseline(int $machineId): JsonResponse
    {
        $user = Auth::user();
        $machine = Machine::where('company_id', $user->company_id)->findOrFail($machineId);

        $baseline = SoftwareBaseline::where('machine_id', $machine->id)
            ->orderBy('software_name')
            ->get();

        return $this->successResponse($baseline, 'Software baseline retrieved successfully');
    }

    /**
     * Get the security baseline for a machine.
     *
     * @param int $machineId The machine ID.
     * @return JsonResponse
     */
    public function securityBaseline(int $machineId): JsonResponse
    {
        $user = Auth::user();
        $machine = Machine::where('company_id', $user->company_id)->findOrFail($machineId);

        $baseline = SecurityBaseline::where('machine_id', $machine->id)
            ->orderBy('component')
            ->get();

        return $this->successResponse($baseline, 'Security baseline retrieved successfully');
    }

    /**
     * Get the configuration baseline for a machine.
     *
     * @param int $machineId The machine ID.
     * @return JsonResponse
     */
    public function configurationBaseline(int $machineId): JsonResponse
    {
        $user = Auth::user();
        $machine = Machine::where('company_id', $user->company_id)->findOrFail($machineId);

        $baseline = ConfigurationBaseline::where('machine_id', $machine->id)
            ->orderBy('setting_key')
            ->get();

        return $this->successResponse($baseline, 'Configuration baseline retrieved successfully');
    }

    /**
     * Resync hardware baseline for a machine.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resyncHardware(Request $request): JsonResponse
    {
        $user = Auth::user();
        $machineId = (int) $request->input('machine_id');
        $machine = Machine::where('company_id', $user->company_id)->findOrFail($machineId);

        $components = $request->input('components', []);
        $this->baselineService->syncHardwareBaseline($machine, $components);

        return $this->successResponse(null, 'Hardware baseline resynced successfully');
    }

    /**
     * Resync software baseline for a machine.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resyncSoftware(Request $request): JsonResponse
    {
        $user = Auth::user();
        $machineId = (int) $request->input('machine_id');
        $machine = Machine::where('company_id', $user->company_id)->findOrFail($machineId);

        $softwareList = $request->input('software', []);
        $this->baselineService->syncSoftwareBaseline($machine, $softwareList);

        return $this->successResponse(null, 'Software baseline resynced successfully');
    }

    /**
     * Resync security baseline for a machine.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resyncSecurity(Request $request): JsonResponse
    {
        $user = Auth::user();
        $machineId = (int) $request->input('machine_id');
        $machine = Machine::where('company_id', $user->company_id)->findOrFail($machineId);

        $securityItems = $request->input('security_items', []);
        $this->baselineService->syncSecurityBaseline($machine, $securityItems);

        return $this->successResponse(null, 'Security baseline resynced successfully');
    }

    /**
     * Resync configuration baseline for a machine.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resyncConfiguration(Request $request): JsonResponse
    {
        $user = Auth::user();
        $machineId = (int) $request->input('machine_id');
        $machine = Machine::where('company_id', $user->company_id)->findOrFail($machineId);

        $configItems = $request->input('config_items', []);
        $this->baselineService->syncConfigurationBaseline($machine, $configItems);

        return $this->successResponse(null, 'Configuration baseline resynced successfully');
    }

    /**
     * Approve a specific change and update the baseline accordingly.
     * When a change is approved:
     * 1. The change record is marked as approved
     * 2. The corresponding baseline is updated to reflect the new state
     * 3. Any related open alert is resolved
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function approveChange(Request $request): JsonResponse
    {
        $user = Auth::user();
        $changeId = (int) $request->input('change_id');
        $companyId = $user->company_id;

        $change = ChangeHistory::where('company_id', $companyId)->findOrFail($changeId);
        $machine = Machine::where('company_id', $companyId)->findOrFail($change->machine_id);

        $category = $change->category;
        $itemIdentifier = $change->item_identifier;
        $newValue = $change->new_value;

        // Update the appropriate baseline based on category.
        switch ($category) {
            case 'hardware':
                $this->approveHardwareChange($machine, $change);
                break;
            case 'software':
                $this->approveSoftwareChange($machine, $change);
                break;
            case 'security':
                $this->approveSecurityChange($machine, $change);
                break;
            case 'configuration':
                $this->approveConfigurationChange($machine, $change);
                break;
            default:
                // Peripheral and network changes don't have dedicated baseline tables yet.
                Log::info("BaselineController: Change approved (no baseline table for {$category})", [
                    'change_id' => $change->id,
                    'category' => $category,
                ]);
                break;
        }

        // Update the change record metadata to indicate approval.
        $metadata = $change->metadata ?? [];
        $metadata['approved_by'] = $user->id;
        $metadata['approved_at'] = now()->toIso8601String();
        $metadata['approval_note'] = $request->input('note', 'Approved by administrator');
        $change->update(['metadata' => $metadata]);

        // Resolve any open alert related to this change.
        Alert::where('machine_id', $machine->id)
            ->where('status', 'open')
            ->where('title', 'LIKE', '%' . ucfirst($category) . '%Change%')
            ->update([
                'status' => 'resolved',
                'resolved_by' => $user->id,
                'resolved_at' => now(),
            ]);

        Log::info("BaselineController: Change approved", [
            'change_id' => $change->id,
            'machine_id' => $machine->id,
            'category' => $category,
        ]);

        return $this->successResponse(null, 'Change approved and baseline updated successfully');
    }

    /**
     * Approve a hardware change by updating the hardware baseline.
     */
    private function approveHardwareChange(Machine $machine, ChangeHistory $change): void
    {
        // Hardware baseline uses component|serial as unique key.
        HardwareBaseline::updateOrCreate(
            [
                'machine_id' => $machine->id,
                'component' => $change->item_identifier ?? $change->item_label ?? 'Unknown',
                'serial_number' => $change->new_value ?? 'unknown',
            ],
            [
                'company_id' => $machine->company_id,
                'baselined_at' => now(),
            ]
        );
    }

    /**
     * Approve a software change by updating the software baseline.
     */
    private function approveSoftwareChange(Machine $machine, ChangeHistory $change): void
    {
        if ($change->change_type === 'removed') {
            SoftwareBaseline::where('machine_id', $machine->id)
                ->where('software_name', $change->item_identifier ?? $change->item_label)
                ->delete();
        } else {
            SoftwareBaseline::updateOrCreate(
                [
                    'machine_id' => $machine->id,
                    'software_name' => $change->item_identifier ?? $change->item_label ?? 'Unknown',
                ],
                [
                    'company_id' => $machine->company_id,
                    'version' => $change->new_value,
                    'baselined_at' => now(),
                ]
            );
        }
    }

    /**
     * Approve a security change by updating the security baseline.
     */
    private function approveSecurityChange(Machine $machine, ChangeHistory $change): void
    {
        SecurityBaseline::updateOrCreate(
            [
                'machine_id' => $machine->id,
                'component' => $change->item_identifier ?? $change->item_label ?? 'Unknown',
            ],
            [
                'company_id' => $machine->company_id,
                'value' => $change->new_value,
                'baselined_at' => now(),
            ]
        );
    }

    /**
     * Approve a configuration change by updating the configuration baseline.
     */
    private function approveConfigurationChange(Machine $machine, ChangeHistory $change): void
    {
        if ($change->change_type === 'removed') {
            ConfigurationBaseline::where('machine_id', $machine->id)
                ->where('setting_key', $change->item_identifier ?? $change->item_label)
                ->delete();
        } else {
            ConfigurationBaseline::updateOrCreate(
                [
                    'machine_id' => $machine->id,
                    'setting_key' => $change->item_identifier ?? $change->item_label ?? 'Unknown',
                ],
                [
                    'company_id' => $machine->company_id,
                    'setting_value' => $change->new_value,
                    'baselined_at' => now(),
                ]
            );
        }
    }
}
