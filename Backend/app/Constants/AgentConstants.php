<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * AgentConstants
 *
 * Central repository for configuration constants used by the
 * DeskGuard agent subsystem. These values govern payload queuing,
 * collection intervals, and offline detection thresholds.
 */
class AgentConstants
{
    /** Maximum number of payloads allowed in the local agent queue */
    public const int MAX_QUEUED_PAYLOADS = 1000;

    /** Maximum total size (bytes) for the on-disk queue file (100 MB) */
    public const int MAX_QUEUE_FILE_SIZE_BYTES = 104857600;

    /** Interval (minutes) at which health data is collected by default */
    public const int DEFAULT_HEALTH_COLLECTION_INTERVAL_MINUTES = 5;

    /** Interval (hours) at which inventory data is collected by default */
    public const int DEFAULT_INVENTORY_COLLECTION_INTERVAL_HOURS = 24;

    /** Minutes of inactivity before a machine is considered offline */
    public const int OFFLINE_THRESHOLD_MINUTES = 15;

    /** Number of consecutive failures before an alert is escalated */
    public const int CONSECUTIVE_ALERT_THRESHOLD = 3;
}
