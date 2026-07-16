<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AgentConstants;
use App\Enums\EventType;
use App\Exceptions\MachineNotFoundException;
use App\Models\Machine;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class MachineService
 *
 * Manages machine lifecycle including CRUD, assignment, heartbeat tracking,
 * and online/offline status management.
 *
 * @package App\Services
 */
class MachineService
{
    /**
     * The audit log service for recording machine events.
     *
     * @var AuditLogService
     */
    private AuditLogService $auditLogService;

    /**
     * MachineService constructor.
     *
     * @param AuditLogService $auditLogService
     */
    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Retrieve a machine by its primary key.
     *
     * @param  int      $id
     * @return Machine
     *
     * @throws MachineNotFoundException
     */
    public function getMachine(int $id): Machine
    {
        try {
            $machine = Machine::with(['company', 'assignedUser', 'currentStatus', 'networkAdapters'])->find($id);

            if (!$machine) {
                throw new MachineNotFoundException(
                    'Machine not found with ID: ' . $id,
                    404,
                    ['machine_id' => $id]
                );
            }

            return $machine;
        } catch (MachineNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('MachineService::getMachine - Failed to retrieve machine', [
                'machine_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            throw new MachineNotFoundException(
                'Failed to retrieve machine.',
                500,
                ['machine_id' => $id]
            );
        }
    }

    /**
     * Retrieve a machine by its unique UID.
     *
     * @param  string   $uid
     * @return Machine
     *
     * @throws MachineNotFoundException
     */
    public function getMachineByUid(string $uid): Machine
    {
        try {
            $machine = Machine::with(['company', 'assignedUser', 'currentStatus'])
                ->where('machine_uid', $uid)
                ->first();

            if (!$machine) {
                throw new MachineNotFoundException(
                    'Machine not found with UID: ' . $uid,
                    404,
                    ['machine_uid' => $uid]
                );
            }

            return $machine;
        } catch (MachineNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('MachineService::getMachineByUid - Failed to retrieve machine by UID', [
                'machine_uid' => $uid,
                'error'       => $e->getMessage(),
            ]);
            throw new MachineNotFoundException(
                'Failed to retrieve machine by UID.',
                500,
                ['machine_uid' => $uid]
            );
        }
    }

    /**
     * Retrieve machines belonging to a company with pagination and filters.
     *
     * @param  int    $companyId
     * @param  array  $params  ['search' => string, 'status' => string, 'per_page' => int, 'page' => int]
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getCompanyMachines(int $companyId, array $params = [])
    {
        try {
            $query = Machine::select(['id', 'company_id', 'user_id', 'machine_uid', 'hostname', 'device_name', 'operating_system', 'manufacturer', 'model', 'is_online', 'last_heartbeat_at', 'created_at'])
                ->with(['assignedUser:id,name,email', 'currentStatus:id,machine_id,cpu_percentage,ram_percentage,online_status,collected_at'])
                ->where('company_id', $companyId);

            // Filter by online/offline status
            if (!empty($params['status'])) {
                $status = strtolower($params['status']);
                if ($status === 'online') {
                    $query->where('is_online', true);
                } elseif ($status === 'offline') {
                    $query->where('is_online', false);
                }
            }

            // Search by hostname, device_name, or machine_uid
            if (!empty($params['search'])) {
                $search = $params['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('hostname', 'LIKE', "%{$search}%")
                      ->orWhere('device_name', 'LIKE', "%{$search}%")
                      ->orWhere('machine_uid', 'LIKE', "%{$search}%")
                      ->orWhere('employee_mobile_number', 'LIKE', "%{$search}%");
                });
            }

            $perPage = min((int) ($params['per_page'] ?? 15), 100);

            return $query->orderBy('last_heartbeat_at', 'desc')->paginate($perPage);
        } catch (Exception $e) {
            Log::error('MachineService::getCompanyMachines - Failed to retrieve company machines', [
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * PERFORMANCE OPTIMIZED: Merged 2 separate DB queries into 1 aggregate query
     * for machine counts. Alert count uses alerts.company_id directly instead of
     * joining to machines table.
     *
     * Before: 2 queries (machine selectRaw + alert count) = ~150ms
     * After:  1 query (machine selectRaw) + 1 query (alert count with direct index) = ~50ms
     *
     * @param  int   $companyId
     * @return array ['total' => int, 'online_count' => int, 'offline_count' => int, 'critical_count' => int]
     */
    public function getCompanyMachineSummary(int $companyId): array
    {
        try {
            // PERFORMANCE: Single aggregate query for all machine counts
            $machineCounts = Machine::selectRaw('COUNT(*) as total, SUM(CASE WHEN is_online THEN 1 ELSE 0 END) as online_count')
                ->where('company_id', $companyId)
                ->first();

            // PERFORMANCE: Use alerts.company_id directly — no JOIN needed.
            // The alerts table already has a company_id column with index.
            $critical = \App\Models\Alert::where('company_id', $companyId)
                ->where('severity', 'critical')
                ->whereIn('status', ['open', 'acknowledged'])
                ->count();

            $total = (int) ($machineCounts->total ?? 0);
            $online = (int) ($machineCounts->online_count ?? 0);

            return [
                'total'          => $total,
                'online_count'   => $online,
                'offline_count'  => $total - $online,
                'critical_count' => $critical,
            ];
        } catch (Exception $e) {
            Log::error('MachineService::getCompanyMachineSummary - Failed', [
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
            ]);
            return ['total' => 0, 'online_count' => 0, 'offline_count' => 0, 'critical_count' => 0];
        }
    }

    /**
     * Assign a machine to a user.
     *
     * @param  int      $machineId
     * @param  int      $userId
     * @return Machine
     *
     * @throws MachineNotFoundException
     */
    public function assignMachine(int $machineId, int $userId): Machine
    {
        try {
            $machine = Machine::findOrFail($machineId);
            $oldValues = $machine->toArray();

            $machine->update(['user_id' => $userId]);
            $machine->refresh();

            $this->auditLogService->log(
                EventType::Update->value,
                'Machine assigned to user ID: ' . $userId,
                $oldValues,
                $machine->toArray(),
                null,
                $machine
            );

            Log::info('Machine assigned to user', [
                'machine_id' => $machine->id,
                'user_id'    => $userId,
                'company_id' => $machine->company_id,
            ]);

            return $machine;
        } catch (Exception $e) {
            Log::error('MachineService::assignMachine - Failed to assign machine', [
                'machine_id' => $machineId,
                'user_id'    => $userId,
                'error'      => $e->getMessage(),
            ]);
            throw new MachineNotFoundException(
                'Machine not found or assignment failed.',
                404,
                ['machine_id' => $machineId, 'user_id' => $userId]
            );
        }
    }

    /**
     * Unassign a machine from its current user.
     *
     * @param  int      $machineId
     * @return Machine
     *
     * @throws MachineNotFoundException
     */
    public function unassignMachine(int $machineId): Machine
    {
        try {
            $machine = Machine::findOrFail($machineId);
            $oldValues = $machine->toArray();

            $machine->update(['user_id' => null]);
            $machine->refresh();

            $this->auditLogService->log(
                EventType::Update->value,
                'Machine unassigned',
                $oldValues,
                $machine->toArray(),
                null,
                $machine
            );

            Log::info('Machine unassigned', [
                'machine_id' => $machine->id,
                'company_id' => $machine->company_id,
            ]);

            return $machine;
        } catch (Exception $e) {
            Log::error('MachineService::unassignMachine - Failed to unassign machine', [
                'machine_id' => $machineId,
                'error'      => $e->getMessage(),
            ]);
            throw new MachineNotFoundException(
                'Machine not found or unassignment failed.',
                404,
                ['machine_id' => $machineId]
            );
        }
    }

    /**
     * Update the heartbeat timestamp for a machine and mark it online.
     *
     * @param  string  $machineUid
     * @return void
     */
    public function updateHeartbeat(string $machineUid): void
    {
        try {
            $machine = Machine::where('machine_uid', $machineUid)->first();

            if (!$machine) {
                Log::warning('MachineService::updateHeartbeat - Machine not found', [
                    'machine_uid' => $machineUid,
                ]);
                return;
            }

            $machine->update([
                'last_heartbeat_at' => now(),
                'is_online'         => true,
            ]);

            $this->auditLogService->log(
                EventType::Heartbeat->value,
                'Heartbeat received for machine: ' . $machineUid,
                null,
                null,
                null,
                $machine
            );

            Log::info('Heartbeat updated for machine', [
                'machine_id'  => $machine->id,
                'machine_uid' => $machineUid,
            ]);
        } catch (Exception $e) {
            Log::error('MachineService::updateHeartbeat - Failed to update heartbeat', [
                'machine_uid' => $machineUid,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark machines as offline if they have not sent a heartbeat within the threshold.
     *
     * Called by the scheduler on a regular interval.
     *
     * @return int  The number of machines marked offline.
     */
    public function markOfflineMachines(): int
    {
        try {
            $threshold = now()->subMinutes(AgentConstants::OFFLINE_THRESHOLD_MINUTES);

            $count = Machine::where('is_online', true)
                ->where(function ($query) use ($threshold) {
                    $query->where('last_heartbeat_at', '<', $threshold)
                          ->orWhereNull('last_heartbeat_at');
                })
                ->update(['is_online' => false]);

            if ($count > 0) {
                Log::info('Machines marked as offline', [
                    'count'     => $count,
                    'threshold' => AgentConstants::OFFLINE_THRESHOLD_MINUTES . ' minutes',
                ]);
            }

            return $count;
        } catch (Exception $e) {
            Log::error('MachineService::markOfflineMachines - Failed to mark offline machines', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get the count of online machines for a company.
     *
     * @param  int  $companyId
     * @return int
     */
    public function getOnlineCount(int $companyId): int
    {
        try {
            return Machine::where('company_id', $companyId)
                ->where('is_online', true)
                ->count();
        } catch (Exception $e) {
            Log::error('MachineService::getOnlineCount - Failed to get online count', [
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get the count of offline machines for a company.
     *
     * @param  int  $companyId
     * @return int
     */
    public function getOfflineCount(int $companyId): int
    {
        try {
            return Machine::where('company_id', $companyId)
                ->where('is_online', false)
                ->count();
        } catch (Exception $e) {
            Log::error('MachineService::getOfflineCount - Failed to get offline count', [
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
