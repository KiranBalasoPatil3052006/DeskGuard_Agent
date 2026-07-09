<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Class AlertGenerationException
 *
 * Thrown when the alert engine encounters a failure while evaluating rules,
 * persisting alerts, or dispatching notifications. This covers internal
 * errors within the alert pipeline.
 *
 * @package App\Exceptions
 */
class AlertGenerationException extends BaseException
{
    /**
     * AlertGenerationException constructor.
     *
     * @param string               $message  A description of the alert engine failure
     * @param int                  $code     HTTP status code (default 500 Internal Server Error)
     * @param array<string, mixed> $context  Contextual data (rule_id, machine_id, payload, etc.)
     */
    public function __construct(string $message, int $code = 500, array $context = [])
    {
        parent::__construct($message, $code, $context);
    }
}
