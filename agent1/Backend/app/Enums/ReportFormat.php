<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * ReportFormat
 *
 * Specifies the output format in which a report can be exported.
 * Supports common document and data-interchange formats.
 */
enum ReportFormat: string
{
    /** Portable Document Format */
    case Pdf = 'pdf';

    /** Microsoft Excel spreadsheet */
    case Excel = 'excel';

    /** Comma-Separated Values */
    case Csv = 'csv';
}
