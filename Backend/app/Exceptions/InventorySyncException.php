<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Class InventorySyncException
 *
 * Thrown when synchronising hardware or software inventory data from managed
 * machines fails. This includes payload deserialisation errors, database
 * persistence failures, or unexpected data formats.
 *
 * @package App\Exceptions
 */
class InventorySyncException extends BaseException
{
    /**
     * InventorySyncException constructor.
     *
     * @param string               $message  A description of the sync failure
     * @param int                  $code     HTTP status code (default 500 Internal Server Error)
     * @param array<string, mixed> $context  Contextual data (machine_id, payload type, etc.)
     */
    public function __construct(string $message, int $code = 500, array $context = [])
    {
        parent::__construct($message, $code, $context);
    }
}
