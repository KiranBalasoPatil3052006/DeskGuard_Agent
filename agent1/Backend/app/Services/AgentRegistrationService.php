<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\RoleConstants;
use App\DTOs\MachineRegistrationDTO;
use App\Enums\EventType;
use App\Exceptions\MachineNotFoundException;
use App\Exceptions\MachineRegistrationException;
use App\Models\Machine;
use App\Models\MachineToken;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class AgentRegistrationService
 *
 * Handles the complete machine agent registration flow.
 * Validates activation tokens, creates machine records, generates API tokens.
 * Every step is logged to the audit log for full traceability.
 *
 * @package App\Services
 */
class AgentRegistrationService
{
    /**
     * The audit log service for recording registration events.
     *
     * @var AuditLogService
     */
    private AuditLogService $auditLogService;

    /**
     * AgentRegistrationService constructor.
     *
     * @param AuditLogService $auditLogService
     */
    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Register a new machine using the provided registration DTO.
     *
     * Validates the activation token, creates the machine record with
     * the associated company, generates a machine API token, and logs
     * the entire process to the audit log.
     *
     * @param  MachineRegistrationDTO  $dto
     * @return Machine
     *
     * @throws MachineRegistrationException
     */
    public function register(MachineRegistrationDTO $dto): Machine
    {
        try {
            $data = $dto->toArray();
            $machineUid = $data['machine_uid'] ?? '';
            $activationToken = $data['activation_token'] ?? '';

            $user = $this->validateActivationToken($activationToken);

            $existingMachine = Machine::where('machine_uid', $machineUid)->first();
            if ($existingMachine) {
                throw new MachineRegistrationException(
                    'A machine with UID "' . $machineUid . '" is already registered.',
                    409,
                    ['machine_uid' => $machineUid, 'company_id' => $user->company_id]
                );
            }

            $machine = DB::transaction(function () use ($data, $user, $machineUid) {
                $machine = Machine::create([
                    'company_id'       => $user->company_id,
                    'user_id'          => $user->id,
                    'machine_uid'      => $machineUid,
                    'hostname'         => $data['hostname'] ?? null,
                    'operating_system' => $data['operating_system'] ?? null,
                    'is_online'        => false,
                    'is_active'        => false,
                    'activation_token' => null,
                ]);

                $apiToken = Str::random(64);
                MachineToken::create([
                    'machine_id' => $machine->id,
                    'token'      => hash('sha256', $apiToken),
                    'expires_at' => now()->addYear(),
                ]);

                $machine->api_token = $apiToken;

                return $machine;
            });

            $this->auditLogService->log(
                EventType::Register->value,
                'Machine registered: ' . $machine->machine_uid . ' for company ID: ' . $user->company_id,
                null,
                $machine->toArray(),
                $user,
                $machine
            );

            Log::info('Machine registered successfully', [
                'machine_id' => $machine->id,
                'machine_uid' => $machine->machine_uid,
                'company_id' => $user->company_id,
                'user_id'    => $user->id,
            ]);

            return $machine;
        } catch (MachineRegistrationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('AgentRegistrationService::register - Registration failed', [
                'machine_uid' => $data['machine_uid'] ?? 'unknown',
                'error'       => $e->getMessage(),
            ]);
            throw new MachineRegistrationException(
                'Machine registration failed due to an internal error.',
                500,
                ['machine_uid' => $data['machine_uid'] ?? 'unknown']
            );
        }
    }

    /**
     * Validate an activation token and return the owning user.
     *
     * Finds a user whose activation_token matches the given value.
     * Throws MachineRegistrationException if the token is invalid.
     *
     * @param  string  $token
     * @return User
     *
     * @throws MachineRegistrationException
     */
    public function validateActivationToken(string $token): User
    {
        try {
            if (empty($token)) {
                throw new MachineRegistrationException(
                    'Activation token cannot be empty.',
                    422,
                    ['token' => $token]
                );
            }

            $user = User::where('activation_token', $token)->first();

            if (!$user) {
                throw new MachineRegistrationException(
                    'Invalid or expired activation token.',
                    422,
                    ['token' => $token]
                );
            }

            if (!$user->is_active) {
                throw new MachineRegistrationException(
                    'The user associated with this activation token is inactive.',
                    403,
                    ['user_id' => $user->id]
                );
            }

            Log::info('Activation token validated successfully', [
                'user_id'    => $user->id,
                'company_id' => $user->company_id,
            ]);

            return $user;
        } catch (MachineRegistrationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('AgentRegistrationService::validateActivationToken - Validation failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            throw new MachineRegistrationException(
                'Failed to validate activation token.',
                500,
                ['token' => $token]
            );
        }
    }

    /**
     * Generate an activation token for an employee user.
     *
     * Creates a unique token string that can be used by the agent
     * during registration.
     *
     * @return string  The generated activation token.
     */
    public function generateActivationToken(): string
    {
        try {
            $token = Str::random(32) . '-' . Str::random(16);

            Log::info('Activation token generated', [
                'token_preview' => substr($token, 0, 8) . '...',
            ]);

            return $token;
        } catch (Exception $e) {
            Log::error('AgentRegistrationService::generateActivationToken - Failed to generate token', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Activate a registered machine.
     *
     * Sets the activated_at timestamp and marks the machine as active.
     *
     * @param  int      $machineId
     * @return Machine
     *
     * @throws MachineNotFoundException
     */
    public function activate(int $machineId): Machine
    {
        try {
            $machine = Machine::findOrFail($machineId);

            $machine->update([
                'activated_at' => now(),
                'is_active'    => true,
            ]);

            $machine->refresh();

            $this->auditLogService->log(
                EventType::Update->value,
                'Machine activated: ' . $machine->machine_uid,
                null,
                $machine->toArray(),
                null,
                $machine
            );

            Log::info('Machine activated successfully', [
                'machine_id'  => $machine->id,
                'machine_uid' => $machine->machine_uid,
            ]);

            return $machine;
        } catch (Exception $e) {
            Log::error('AgentRegistrationService::activate - Failed to activate machine', [
                'machine_id' => $machineId,
                'error'      => $e->getMessage(),
            ]);
            throw new MachineNotFoundException(
                'Machine not found or activation failed.',
                404,
                ['machine_id' => $machineId]
            );
        }
    }
}
