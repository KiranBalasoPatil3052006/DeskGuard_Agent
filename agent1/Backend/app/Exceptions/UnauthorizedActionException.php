<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Class UnauthorizedActionException
 *
 * Thrown when the authenticated user attempts an action they do not have
 * permission to perform (e.g., accessing another company's resources or
 * performing a restricted operation).
 *
 * @package App\Exceptions
 */
class UnauthorizedActionException extends BaseException
{
    /**
     * UnauthorizedActionException constructor.
     *
     * @param string               $message  A description of the unauthorised action
     * @param int                  $code     HTTP status code (default 403 Forbidden)
     * @param array<string, mixed> $context  Contextual data (user_id, permission, etc.)
     */
    public function __construct(string $message, int $code = 403, array $context = [])
    {
        parent::__construct($message, $code, $context);
    }
}
