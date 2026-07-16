<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\HealthDataDTO;
use App\Services\MonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class ProcessHealthDataJob
 *
 * Processes a health data payload received from a machine agent.
 * Converts the raw payload into a HealthDataDTO and delegates the
 * actual processing to MonitoringService. If the data is valid,
 * an AlertGenerationJob is dispatched after processing.
 *
 * @package App\Jobs
 */
class ProcessHealthDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The health data payload from the machine agent.
     *
     * @var array
     */
    public array $data;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param  array  $data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job by processing health data through MonitoringService.
     *
     * Converts the raw array payload to a HealthDataDTO before passing
     * it to the service for processing and alert evaluation.
     *
     * @param  MonitoringService  $monitoringService
     * @return void
     */
    public function handle(MonitoringService $monitoringService): void
    {
        Log::info('ProcessHealthDataJob started', [
            'machine_uid' => $this->data['machine_uid'] ?? 'unknown',
        ]);

        $dto = HealthDataDTO::fromArray($this->data);

        $monitoringService->processHealthData($dto);

        Log::info('ProcessHealthDataJob completed', [
            'machine_uid' => $this->data['machine_uid'] ?? 'unknown',
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function failed(\Throwable $e): void
    {
        Log::error('ProcessHealthDataJob failed', [
            'machine_uid' => $this->data['machine_uid'] ?? 'unknown',
            'data' => $this->data,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
