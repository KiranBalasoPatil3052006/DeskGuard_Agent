<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Machine;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminSearchController extends Controller
{
    use ApiResponseTrait;

    public function search(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'query' => 'required|string|max:255',
            ]);

            $query = $validated['query'];

            $users = User::where('mobile_number', 'LIKE', "%{$query}%")
                ->orWhere('name', 'LIKE', "%{$query}%")
                ->orWhere('email', 'LIKE', "%{$query}%")
                ->with(['machines' => function ($q) {
                    $q->with('currentStatus');
                }])
                ->get();

            $machines = Machine::where('machine_uid', 'LIKE', "%{$query}%")
                ->orWhere('hostname', 'LIKE', "%{$query}%")
                ->orWhere('device_name', 'LIKE', "%{$query}%")
                ->with('currentStatus')
                ->get();

            $results = $users->map(function ($user) {
                return [
                    'type' => 'user',
                    'id' => $user->id,
                    'name' => $user->name,
                    'mobile_number' => $user->mobile_number,
                    'email' => $user->email,
                    'is_verified' => $user->is_verified,
                    'machines' => $user->machines,
                ];
            })->concat($machines->map(function ($machine) {
                return [
                    'type' => 'machine',
                    'id' => $machine->id,
                    'machine_uid' => $machine->machine_uid,
                    'hostname' => $machine->hostname,
                    'device_name' => $machine->device_name,
                    'status' => $machine->status,
                    'is_online' => $machine->is_online,
                    'current_status' => $machine->currentStatus,
                ];
            }));

            return $this->successResponse($results, 'Search results retrieved.');
        } catch (Exception $e) {
            Log::error('AdminSearchController::search failed', [
                'query' => $request->input('query'),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Search failed.', [], 500);
        }
    }

    public function userDetail(int $id): JsonResponse
    {
        try {
            $user = User::with(['machines' => function ($q) {
                $q->with(['currentStatus', 'deviceEvents' => function ($q) {
                    $q->latest()->limit(20);
                }]);
            }, 'company'])->findOrFail($id);

            return $this->successResponse($user, 'User details retrieved.');
        } catch (Exception $e) {
            Log::error('AdminSearchController::userDetail failed', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('User not found.', [], 404);
        }
    }
}
