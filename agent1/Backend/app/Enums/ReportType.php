<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * ReportType
 *
 * Classifies the kind of report that can be generated.
 * Each type corresponds to a different domain of machine data.
 */
enum ReportType: string
{
    /** Report covering machine health metrics (CPU, RAM, disk, etc.) */
    case Health = 'health';

    /** Report detailing installed hardware and software inventory */
    case Inventory = 'inventory';

    /** Report focusing on security posture and events */
    case Security = 'security';

    /** User-defined custom report */
    case Custom = 'custom';
}
