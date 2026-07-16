<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * AlertSeverity
 *
 * Defines the severity levels for system alerts.
 * Used to categorise alerts by their urgency and impact on operations.
 */
enum AlertSeverity: string
{
    /** Informational notice, no immediate action required */
    case Info = 'info';

    /** Warning condition that may require attention */
    case Warning = 'warning';

    /** Critical condition requiring immediate action */
    case Critical = 'critical';
}
