<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Class MachineNotFoundException
 *
 * Thrown when a lookup for a machine by its unique identifier (UID) yields
 * no result. This ensures callers can distinguish "not found" from other
 * error conditions.
 *
 * @package App\Exceptions
 */
class MachineNotFoundException extends BaseException
{
    /**
     * MachineNotFoundException constructor.
     *
     * @param string               $message  A description of the failed lookup
     * @param int                  $code     HTTP status code (default 404 Not Found)
     * @param array<string, mixed> $context  Contextual data (machine_uid, company_id, etc.)
     */
    public function __construct(string $message, int $code = 404, array $context = [])
    {
        parent::__construct($message, $code, $context);
    }
}
