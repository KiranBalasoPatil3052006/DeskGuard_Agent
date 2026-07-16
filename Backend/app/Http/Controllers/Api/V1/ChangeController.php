<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChangeHistory;
use App\Models\Machine;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChangeController extends Controller
{
    use ApiResponseTrait;

    /**
     * Paginated change history with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $query = ChangeHistory::where('company_id', $companyId)
            ->with('machine:id,machine_uid,hostname,device_name');

        if ($request->filled('category')) {
            $query->byCategory($request->input('category'));
        }

        if ($request->filled('severity')) {
            $query->bySeverity($request->input('severity'));
        }

        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('machine_id')) {
            $query->byMachine((int) $request->input('machine_id'));
        }

        if ($request->filled('days')) {
            $query->recentChange((int) $request->input('days'));
        }

        if ($request->filled('date_from')) {
            $query->where('detected_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('detected_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $perPage = min((int) $request->input('per_page', 50), 100);
        $changes = $query->orderBy('detected_at', 'desc')->paginate($perPage);

        return $this->successResponse($changes, 'Change history retrieved successfully');
    }

    /**
     * Changes for a specific machine with filters.
     */
    public function machineChanges(Request $request, int $machineId): JsonResponse
    {
        $user = Auth::user();
        $machine = Machine::where('company_id', $user->company_id)->findOrFail($machineId);

        $query = ChangeHistory::where('machine_id', $machine->id);

        if ($request->filled('category')) {
            $query->byCategory($request->input('category'));
        }

        if ($request->filled('severity')) {
            $query->bySeverity($request->input('severity'));
        }

        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('days')) {
            $query->recentChange((int) $request->input('days'));
        }

        if ($request->filled('date_from')) {
            $query->where('detected_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('detected_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $perPage = min((int) $request->input('per_page', 50), 100);
        $changes = $query->orderBy('detected_at', 'desc')->paginate($perPage);

        return $this->successResponse($changes, 'Machine change history retrieved successfully');
    }

    /**
     * Recent changes across the company.
     */
    public function recentChanges(Request $request): JsonResponse
    {
        $user = Auth::user();
        $days = min((int) $request->input('days', 7), 90);
        $limit = min((int) $request->input('limit', 10), 50);

        $changes = ChangeHistory::where('company_id', $user->company_id)
            ->recentChange($days)
            ->with('machine:id,machine_uid,hostname,device_name')
            ->orderBy('detected_at', 'desc')
            ->limit($limit)
            ->get()
            ->append('recommendation');

        return $this->successResponse($changes, 'Recent changes retrieved successfully');
    }

    /**
     * Change summary with counts by category.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $days = min((int) $request->input('days', 7), 90);

        $since = now()->subDays($days);

        $categoryCounts = ChangeHistory::where('company_id', $companyId)
            ->where('detected_at', '>=', $since)
            ->selectRaw('category, change_type, COUNT(*) as count')
            ->groupBy('category', 'change_type')
            ->get();

        $totalChanges = $categoryCounts->sum('count');
        $byCategory = $categoryCounts->groupBy('category')->map(fn($items) => [
            'total' => $items->sum('count'),
            'by_type' => $items->pluck('count', 'change_type'),
        ]);

        return $this->successResponse([
            'total_changes' => $totalChanges,
            'by_category' => $byCategory,
            'detail' => $categoryCounts,
        ], 'Change summary retrieved successfully');
    }

    /**
     * Update the investigation status of a change.
     * Valid statuses: pending_review, investigating, approved, resolved, false_positive
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:pending_review,investigating,approved,resolved,false_positive',
            'note' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $change = ChangeHistory::where('company_id', $user->company_id)->findOrFail($id);

        $metadata = $change->metadata ?? [];
        $metadata['status_updated_by'] = $user->id;
        $metadata['status_updated_at'] = now()->toIso8601String();
        if ($request->filled('note')) {
            $metadata['status_note'] = $request->input('note');
        }

        $change->update([
            'status' => $request->input('status'),
            'metadata' => $metadata,
        ]);

        Log::info("ChangeController: Status updated for change {$id}", [
            'change_id' => $id,
            'new_status' => $request->input('status'),
            'user_id' => $user->id,
        ]);

        return $this->successResponse($change->fresh()->append('recommendation'), 'Change status updated successfully');
    }
}
