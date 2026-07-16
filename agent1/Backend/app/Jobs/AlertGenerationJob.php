<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Machine;
use App\Models\MachineCurrentStatus;
use App\Services\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Class AlertGenerationJob
 *
 * Evaluates alert rules against a machine's current status and creates
 * alerts if any configured thresholds are breached. Runs as a queued,
 * unique job to prevent duplicate evaluations for the same machine.
 *
 * @package App\Jobs
 */
class AlertGenerationJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The machine to evaluate alerts for.
     *
     * @var Machine
     */
    public Machine $machine;

    /**
     * The current status snapshot of the machine.
     *
     * @var MachineCurrentStatus
     */
    public MachineCurrentStatus $status;

    /**
     * The number of seconds the unique lock is held.
     *
     * @var int
     */
    public int $uniqueFor = 60;

    /**
     * Create a new job instance.
     *
     * @param  Machine              $machine
     * @param  MachineCurrentStatus $status
     * @return void
     */
    public function __construct(Machine $machine, MachineCurrentStatus $status)
    {
        $this->machine = $machine;
        $this->status = $status;
    }

    /**
     * Execute the job by evaluating alert rules for the machine.
     *
     * @param  AlertService  $alertService
     * @return void
     */
    public function handle(AlertService $alertService): void
    {
        Log::info('AlertGenerationJob started', [
            'machine_id' => $this->machine->id,
            'machine_uid' => $this->machine->machine_uid,
        ]);

        $alertService->evaluateMachineAlerts($this->machine, $this->status);

        Log::info('AlertGenerationJob completed', [
            'machine_id' => $this->machine->id,
            'machine_uid' => $this->machine->machine_uid,
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @param  Throwable  $e
     * @return void
     */
    public function failed(Throwable $e): void
    {
        Log::error('AlertGenerationJob failed', [
            'machine_id' => $this->machine->id ?? null,
            'machine_uid' => $this->machine->machine_uid ?? null,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Get the unique ID for the job.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return 'alert-generation-' . $this->machine->id;
    }
}
