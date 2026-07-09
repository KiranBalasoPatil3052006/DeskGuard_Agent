<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\InventoryDataDTO;
use App\Services\InventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class ProcessInventoryDataJob
 *
 * Processes inventory data asynchronously. Handles hardware inventory,
 * software inventory, and security data received from machine agents.
 * Converts the raw payload into an InventoryDataDTO before passing
 * it to the InventoryService.
 *
 * @package App\Jobs
 */
class ProcessInventoryDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The inventory data payload from the machine agent.
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
     * Execute the job by processing inventory data through InventoryService.
     *
     * Converts the raw array payload to an InventoryDataDTO and passes it
     * to the service for hardware and software inventory processing.
     *
     * @param  InventoryService  $inventoryService
     * @return void
     */
    public function handle(InventoryService $inventoryService): void
    {
        Log::info('ProcessInventoryDataJob started', [
            'machine_uid' => $this->data['machine_uid'] ?? 'unknown',
        ]);

        $dto = InventoryDataDTO::fromArray($this->data);

        $inventoryService->processHardwareInventory($dto);
        $inventoryService->processSoftwareInventory($dto);

        Log::info('ProcessInventoryDataJob completed', [
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
        Log::error('ProcessInventoryDataJob failed', [
            'machine_uid' => $this->data['machine_uid'] ?? 'unknown',
            'data' => $this->data,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
