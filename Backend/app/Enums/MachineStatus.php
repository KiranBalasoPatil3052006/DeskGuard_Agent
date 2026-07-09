<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * MachineStatus
 *
 * Indicates the current connectivity state of a monitored machine.
 * Used by the heartbeat mechanism and reporting to determine
 * whether a machine is reachable.
 */
enum MachineStatus: string
{
    /** Machine is reachable and reporting normally */
    case Online = 'online';

    /** Machine is unreachable or has stopped reporting */
    case Offline = 'offline';

    /** Connectivity state has not yet been determined */
    case Unknown = 'unknown';
}
