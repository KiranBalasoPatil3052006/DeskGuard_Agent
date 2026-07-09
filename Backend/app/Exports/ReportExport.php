<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Class ReportExport
 *
 * Generic Excel export for DeskGuard reports. Takes a report data array
 * and outputs the first level as Excel rows with headings.
 *
 * @package App\Exports
 */
class ReportExport implements FromArray, WithHeadings, WithTitle
{
    /**
     * The report data payload.
     *
     * @var array
     */
    protected array $data;

    /**
     * ReportExport constructor.
     *
     * @param array $data The full report data.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Return the array of rows for the Excel sheet.
     *
     * @return array
     */
    public function array(): array
    {
        $rows = [];
        foreach ($this->data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $sub) {
                    if (is_array($sub)) {
                        $rows[] = $this->flattenRow($sub);
                    }
                }
            } else {
                $rows[] = [$key, $value];
            }
        }
        return $rows;
    }

    /**
     * Flatten a nested row for Excel output.
     *
     * @param  array  $item
     * @return array
     */
    private function flattenRow(array $item): array
    {
        $result = [];
        foreach ($item as $k => $v) {
            $result[] = is_array($v) ? json_encode($v) : (string) $v;
        }
        return $result;
    }

    /**
     * Return the column headings.
     *
     * @return array
     */
    public function headings(): array
    {
        return array_keys($this->data);
    }

    /**
     * Return the sheet title.
     *
     * @return string
     */
    public function title(): string
    {
        return $this->data['title'] ?? 'Report';
    }
}
