<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class ReportGenerationJob
 *
 * Generates a report file (PDF, Excel, or CSV) asynchronously, stores
 * it in the storage/app/reports/ directory, creates a corresponding
 * Report record in the database, and sends a notification to the
 * requesting user upon completion.
 *
 * @package App\Jobs
 */
class ReportGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The company ID for which the report is generated.
     *
     * @var int
     */
    public int $companyId;

    /**
     * The ID of the user who requested the report.
     *
     * @var int
     */
    public int $userId;

    /**
     * The report type (health, inventory, security).
     *
     * @var string
     */
    public string $type;

    /**
     * The output format (pdf, excel, csv).
     *
     * @var string
     */
    public string $format;

    /**
     * Optional filters to apply to the report data.
     *
     * @var array|null
     */
    public ?array $filters;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param  int        $companyId
     * @param  int        $userId
     * @param  string     $type
     * @param  string     $format
     * @param  array|null $filters
     * @return void
     */
    public function __construct(int $companyId, int $userId, string $type, string $format, ?array $filters = null)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->type = $type;
        $this->format = $format;
        $this->filters = $filters;
    }

    /**
     * Execute the job by generating the report and notifying the user.
     *
     * @param  ReportService  $reportService
     * @return void
     */
    public function handle(ReportService $reportService): void
    {
        Log::info('ReportGenerationJob started', [
            'company_id' => $this->companyId,
            'user_id' => $this->userId,
            'type' => $this->type,
            'format' => $this->format,
        ]);

        $report = $reportService->generateReport(
            $this->companyId,
            $this->userId,
            $this->type,
            $this->format,
            $this->filters,
        );

        Log::info('ReportGenerationJob completed', [
            'report_id' => $report->id,
            'company_id' => $this->companyId,
            'type' => $this->type,
            'format' => $this->format,
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
        Log::error('ReportGenerationJob failed', [
            'company_id' => $this->companyId ?? null,
            'user_id' => $this->userId ?? null,
            'type' => $this->type ?? null,
            'format' => $this->format ?? null,
            'filters' => $this->filters ?? null,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
