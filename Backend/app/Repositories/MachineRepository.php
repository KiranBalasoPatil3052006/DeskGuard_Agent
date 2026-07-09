<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Machine;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class MachineRepository
 *
 * Repository for Machine-related database operations.
 * Extends BaseRepository with machine-specific query methods.
 *
 * @package App\Repositories
 */
class MachineRepository extends BaseRepository
{
    /**
     * MachineRepository constructor.
     *
     * @param Machine $machine The Machine model instance.
     */
    public function __construct(Machine $machine)
    {
        parent::__construct($machine);
    }

    /**
     * Find a machine by its unique UID string.
     *
     * @param string $machineUid The unique machine identifier.
     * @return Machine|null The machine instance if found, null otherwise.
     */
    public function findByUid(string $machineUid): ?Machine
    {
        try {
            return $this->model->where('machine_uid', '=', $machineUid)->first();
        } catch (\Throwable $e) {
            Log::error('MachineRepository::findByUid - Failed to find machine by UID', [
                'machineUid' => $machineUid,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Retrieve all machines belonging to a specific company.
     *
     * @param int $companyId The company ID.
     * @return Collection A collection of machines for the given company.
     */
    public function findByCompany(int $companyId): Collection
    {
        try {
            return $this->model->where('company_id', '=', $companyId)->get();
        } catch (\Throwable $e) {
            Log::error('MachineRepository::findByCompany - Failed to find machines by company', [
                'companyId' => $companyId,
                'error'     => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve all machines that are currently marked as online.
     *
     * @return Collection A collection of online machines.
     */
    public function findOnline(): Collection
    {
        try {
            return $this->model->where('is_online', '=', true)->get();
        } catch (\Throwable $e) {
            Log::error('MachineRepository::findOnline - Failed to retrieve online machines', [
                'error' => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve all machines that are currently marked as offline.
     *
     * @return Collection A collection of offline machines.
     */
    public function findOffline(): Collection
    {
        try {
            return $this->model->where('is_online', '=', false)->get();
        } catch (\Throwable $e) {
            Log::error('MachineRepository::findOffline - Failed to retrieve offline machines', [
                'error' => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Retrieve all machines assigned to a specific user.
     *
     * @param int $userId The user ID.
     * @return Collection A collection of machines assigned to the user.
     */
    public function findByUser(int $userId): Collection
    {
        try {
            return $this->model->where('user_id', '=', $userId)->get();
        } catch (\Throwable $e) {
            Log::error('MachineRepository::findByUser - Failed to find machines by user', [
                'userId' => $userId,
                'error'  => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Update the heartbeat timestamp for a given machine.
     *
     * @param int $machineId The machine ID.
     * @return bool True if the heartbeat was updated, false otherwise.
     */
    public function updateHeartbeat(int $machineId): bool
    {
        try {
            $machine = $this->model->find($machineId);

            if (!$machine) {
                Log::warning('MachineRepository::updateHeartbeat - Machine not found', [
                    'machineId' => $machineId,
                ]);
                return false;
            }

            $machine->last_heartbeat_at = Carbon::now();
            $machine->is_online         = true;

            return $machine->save();
        } catch (\Throwable $e) {
            Log::error('MachineRepository::updateHeartbeat - Failed to update heartbeat', [
                'machineId' => $machineId,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Mark machines with a heartbeat older than the given threshold as offline.
     *
     * @param int $minutes The number of minutes after which a machine is considered offline.
     * @return int The number of machines that were marked offline.
     */
    public function markOffline(int $minutes = 15): int
    {
        try {
            $threshold = Carbon::now()->subMinutes($minutes);

            return $this->model
                ->where('is_online', '=', true)
                ->where('last_heartbeat_at', '<', $threshold)
                ->update(['is_online' => false]);
        } catch (\Throwable $e) {
            Log::error('MachineRepository::markOffline - Failed to mark machines offline', [
                'minutes' => $minutes,
                'error'   => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
