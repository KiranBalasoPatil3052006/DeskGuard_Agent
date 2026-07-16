<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventType;
use App\Enums\ReportFormat;
use App\Enums\ReportType;
use App\Models\Machine;
use App\Models\Report;
use Dompdf\Dompdf;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class ReportService
 *
 * Handles report generation, retrieval, and download for health, inventory,
 * and security data. Supports multiple output formats (PDF, Excel, CSV).
 *
 * @package App\Services
 */
class ReportService
{
    /**
     * The audit log service for recording report events.
     *
     * @var AuditLogService
     */
    private AuditLogService $auditLogService;

    /**
     * ReportService constructor.
     *
     * @param AuditLogService $auditLogService
     */
    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Generate a report of the specified type and format.
     *
     * @param  int        $companyId
     * @param  int        $userId
     * @param  string     $type
     * @param  string     $format
     * @param  array|null $filters
     * @return Report
     */
    public function generateReport(int $companyId, int $userId, string $type, string $format, ?array $filters = null): Report
    {
        try {
            $report = match ($type) {
                ReportType::Health->value    => $this->generateHealthReport($companyId, $format),
                ReportType::Inventory->value => $this->generateInventoryReport($companyId, $format),
                ReportType::Security->value  => $this->generateSecurityReport($companyId, $format),
                default                      => throw new Exception('Unsupported report type: ' . $type),
            };

            $report->update([
                'generated_by' => $userId,
                'filters'      => $filters,
            ]);

            $report->refresh();

            $this->auditLogService->log(
                EventType::Create->value,
                'Report generated: ' . $report->name . ' (' . $type . ', ' . $format . ')',
                null,
                $report->toArray(),
                null,
                null
            );

            Log::info('Report generated successfully', [
                'report_id'  => $report->id,
                'company_id' => $companyId,
                'type'       => $type,
                'format'     => $format,
            ]);

            return $report;
        } catch (Exception $e) {
            Log::error('ReportService::generateReport - Failed to generate report', [
                'company_id' => $companyId,
                'type'       => $type,
                'format'     => $format,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get all reports for a company.
     *
     * @param  int  $companyId
     * @return Collection<int, Report>
     */
    public function getCompanyReports(int $companyId): Collection
    {
        try {
            return Report::with(['generator'])
                ->where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (Exception $e) {
            Log::error('ReportService::getCompanyReports - Failed to retrieve company reports', [
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Download a report file by its ID.
     *
     * @param  int       $reportId
     * @return Response
     */
    public function downloadReport(int $reportId): Response
    {
        try {
            $report = Report::findOrFail($reportId);

            if (!$report->file_path || !file_exists(storage_path('app/' . $report->file_path))) {
                throw new Exception('Report file not found.');
            }

            $filePath = storage_path('app/' . $report->file_path);
            $mimeType = $report->mime_type ?? 'application/octet-stream';
            $fileName = $report->name . '.' . pathinfo($report->file_path, PATHINFO_EXTENSION);

            Log::info('Report downloaded', [
                'report_id' => $report->id,
                'name'      => $report->name,
            ]);

            return response()->download($filePath, $fileName, ['Content-Type' => $mimeType]);
        } catch (Exception $e) {
            Log::error('ReportService::downloadReport - Failed to download report', [
                'report_id' => $reportId,
                'error'     => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate a health report for a company.
     *
     * @param  int     $companyId
     * @param  string  $format
     * @return Report
     */
    public function generateHealthReport(int $companyId, string $format): Report
    {
        try {
            $machines = Machine::with(['currentStatus', 'healthLogs' => function ($query) {
                $query->latest()->take(100);
            }])->where('company_id', $companyId)->get();

            $reportName = 'Health Report - ' . now()->format('Y-m-d H:i');
            $reportData = [
                'title'        => $reportName,
                'type'         => 'Health',
                'company_id'   => $companyId,
                'generated_at' => now()->toDateTimeString(),
                'machines'     => $machines->toArray(),
            ];

            $result = $this->writeReportFile($reportData, $format, $companyId, 'health-report');

            $report = Report::create([
                'company_id'   => $companyId,
                'name'         => $reportName,
                'type'         => ReportType::Health->value,
                'file_path'    => $result['file_path'],
                'mime_type'    => $result['mime_type'],
                'file_size'    => $result['file_size'],
                'generated_at' => now(),
            ]);

            Log::info('Health report generated', [
                'report_id'  => $report->id,
                'company_id' => $companyId,
                'format'     => $format,
            ]);

            return $report;
        } catch (Exception $e) {
            Log::error('ReportService::generateHealthReport - Failed to generate health report', [
                'company_id' => $companyId,
                'format'     => $format,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate an inventory report for a company.
     *
     * @param  int     $companyId
     * @param  string  $format
     * @return Report
     */
    public function generateInventoryReport(int $companyId, string $format): Report
    {
        try {
            $machines = Machine::with(['hardwareInventories', 'softwareInventories'])
                ->where('company_id', $companyId)
                ->get();

            $reportName = 'Inventory Report - ' . now()->format('Y-m-d H:i');
            $reportData = [
                'title'        => $reportName,
                'type'         => 'Inventory',
                'company_id'   => $companyId,
                'generated_at' => now()->toDateTimeString(),
                'machines'     => $machines->toArray(),
            ];

            $result = $this->writeReportFile($reportData, $format, $companyId, 'inventory-report');

            $report = Report::create([
                'company_id'   => $companyId,
                'name'         => $reportName,
                'type'         => ReportType::Inventory->value,
                'file_path'    => $result['file_path'],
                'mime_type'    => $result['mime_type'],
                'file_size'    => $result['file_size'],
                'generated_at' => now(),
            ]);

            Log::info('Inventory report generated', [
                'report_id'  => $report->id,
                'company_id' => $companyId,
                'format'     => $format,
            ]);

            return $report;
        } catch (Exception $e) {
            Log::error('ReportService::generateInventoryReport - Failed to generate inventory report', [
                'company_id' => $companyId,
                'format'     => $format,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate a security report for a company.
     *
     * @param  int     $companyId
     * @param  string  $format
     * @return Report
     */
    public function generateSecurityReport(int $companyId, string $format): Report
    {
        try {
            $machines = Machine::with(['antivirusStatuses', 'firewallStatuses', 'loginActivities', 'usbActivities'])
                ->where('company_id', $companyId)
                ->get();

            $reportName = 'Security Report - ' . now()->format('Y-m-d H:i');
            $reportData = [
                'title'        => $reportName,
                'type'         => 'Security',
                'company_id'   => $companyId,
                'generated_at' => now()->toDateTimeString(),
                'machines'     => $machines->toArray(),
            ];

            $result = $this->writeReportFile($reportData, $format, $companyId, 'security-report');

            $report = Report::create([
                'company_id'   => $companyId,
                'name'         => $reportName,
                'type'         => ReportType::Security->value,
                'file_path'    => $result['file_path'],
                'mime_type'    => $result['mime_type'],
                'file_size'    => $result['file_size'],
                'generated_at' => now(),
            ]);

            Log::info('Security report generated', [
                'report_id'  => $report->id,
                'company_id' => $companyId,
                'format'     => $format,
            ]);

            return $report;
        } catch (Exception $e) {
            Log::error('ReportService::generateSecurityReport - Failed to generate security report', [
                'company_id' => $companyId,
                'format'     => $format,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a report by its ID, removing the file from storage.
     *
     * @param  int  $reportId
     * @return void
     */
    public function deleteReport(int $reportId): void
    {
        try {
            $report = Report::findOrFail($reportId);

            if ($report->file_path && file_exists(storage_path('app/' . $report->file_path))) {
                unlink(storage_path('app/' . $report->file_path));
            }

            $report->delete();

            Log::info('Report deleted', [
                'report_id' => $reportId,
                'name'      => $report->name,
            ]);
        } catch (Exception $e) {
            Log::error('ReportService::deleteReport - Failed to delete report', [
                'report_id' => $reportId,
                'error'     => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Write report content to a file in the requested format.
     *
     * @param  array   $data      Report data payload.
     * @param  string  $format    Output format (pdf, excel, csv).
     * @param  int     $companyId
     * @param  string  $slug      Base filename slug.
     * @return array{file_path: string, mime_type: string, file_size: int}
     */
    private function writeReportFile(array $data, string $format, int $companyId, string $slug): array
    {
        $extension = match ($format) {
            ReportFormat::Pdf->value   => 'pdf',
            ReportFormat::Excel->value => 'xlsx',
            ReportFormat::Csv->value   => 'csv',
            default                    => 'json',
        };

        $mimeType = $this->getMimeType($format);
        $fileName = $slug . '-' . Str::slug(now()->toDateTimeString()) . '.' . $extension;
        $filePath = 'reports/' . $companyId . '/' . $fileName;
        $fullPath = storage_path('app/' . $filePath);

        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        switch ($format) {
            case ReportFormat::Pdf->value:
                $html = $this->buildReportHtml($data);
                $dompdf = new Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                file_put_contents($fullPath, $dompdf->output());
                break;

            case ReportFormat::Excel->value:
                $export = new \App\Exports\ReportExport($data);
                Excel::store($export, $filePath, 'local');
                break;

            case ReportFormat::Csv->value:
                $handle = fopen($fullPath, 'w');
                if ($handle) {
                    fputcsv($handle, ['Key', 'Value']);
                    $this->writeCsvRows($handle, $data, '');
                    fclose($handle);
                }
                break;

            default:
                file_put_contents($fullPath, json_encode($data, JSON_PRETTY_PRINT));
                break;
        }

        return [
            'file_path' => $filePath,
            'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            'mime_type' => $mimeType,
        ];
    }

    /**
     * Build a simple HTML table from report data for PDF rendering.
     *
     * @param  array   $data
     * @return string
     */
    private function buildReportHtml(array $data): string
    {
        $title = htmlspecialchars($data['title'] ?? 'Report');
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
            h1 { color: #333; border-bottom: 2px solid #0066cc; padding-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background: #0066cc; color: white; padding: 6px; text-align: left; }
            td { border: 1px solid #ccc; padding: 4px; }
            tr:nth-child(even) { background: #f5f5f5; }
            .meta { color: #666; font-size: 9pt; margin-bottom: 15px; }
        </style></head><body>';
        $html .= '<h1>' . $title . '</h1>';
        $html .= '<div class="meta">Generated: ' . htmlspecialchars($data['generated_at'] ?? '') . '</div>';
        $html .= '<table><thead><tr><th>Field</th><th>Value</th></tr></thead><tbody>';

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $html .= '<tr><td colspan="2"><strong>' . htmlspecialchars((string) $key) . '</strong></td></tr>';
                $html .= $this->buildHtmlRows($value, '');
            } else {
                $html .= '<tr><td>' . htmlspecialchars((string) $key) . '</td>';
                $html .= '<td>' . htmlspecialchars((string) $value) . '</td></tr>';
            }
        }

        $html .= '</tbody></table></body></html>';
        return $html;
    }

    /**
     * Recursively build HTML table rows for nested data.
     *
     * @param  array   $data
     * @param  string  $prefix
     * @return string
     */
    private function buildHtmlRows(array $data, string $prefix): string
    {
        $rows = '';
        foreach ($data as $key => $value) {
            $label = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value)) {
                $rows .= '<tr><td colspan="2"><strong>' . htmlspecialchars((string) $label) . '</strong></td></tr>';
                $rows .= $this->buildHtmlRows($value, $label);
            } else {
                $rows .= '<tr><td>' . htmlspecialchars((string) $label) . '</td>';
                $rows .= '<td>' . htmlspecialchars((string) $value) . '</td></tr>';
            }
        }
        return $rows;
    }

    /**
     * Recursively write nested array to CSV rows.
     *
     * @param  resource  $handle
     * @param  array     $data
     * @param  string    $prefix
     * @return void
     */
    private function writeCsvRows($handle, array $data, string $prefix): void
    {
        foreach ($data as $key => $value) {
            $label = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value)) {
                $this->writeCsvRows($handle, $value, $label);
            } else {
                fputcsv($handle, [$label, is_string($value) ? $value : json_encode($value)]);
            }
        }
    }

    /**
     * Get the MIME type for a given report format.
     *
     * @param  string  $format
     * @return string
     */
    private function getMimeType(string $format): string
    {
        return match ($format) {
            ReportFormat::Pdf->value   => 'application/pdf',
            ReportFormat::Excel->value => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ReportFormat::Csv->value   => 'text/csv',
            default                    => 'application/json',
        };
    }
}
