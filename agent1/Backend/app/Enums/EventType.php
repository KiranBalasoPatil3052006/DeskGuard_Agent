<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * EventType
 *
 * Enumerates all possible event types that can be recorded
 * by the DeskGuard activity logging system. Each event type
 * corresponds to a distinct action performed by a user or the system.
 */
enum EventType: string
{
    /** User signed in to the system */
    case Login = 'login';

    /** User signed out of the system */
    case Logout = 'logout';

    /** A new resource was created */
    case Create = 'create';

    /** An existing resource was modified */
    case Update = 'update';

    /** A resource was removed */
    case Delete = 'delete';

    /** A new entity was registered (e.g. new machine) */
    case Register = 'register';

    /** Data synchronisation occurred */
    case Sync = 'sync';

    /** Periodic heartbeat signal from a monitored machine */
    case Heartbeat = 'heartbeat';
}
