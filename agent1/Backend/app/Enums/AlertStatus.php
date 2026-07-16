<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * AlertStatus
 *
 * Represents the lifecycle states an alert can transition through.
 * Alerts are created as Open, can be Acknowledged by operators,
 * and finally Resolved once the underlying issue is addressed.
 */
enum AlertStatus: string
{
    /** Alert has been raised but not yet reviewed */
    case Open = 'open';

    /** Alert has been seen and acknowledged by an operator */
    case Acknowledged = 'acknowledged';

    /** Underlying issue has been resolved */
    case Resolved = 'resolved';
}
