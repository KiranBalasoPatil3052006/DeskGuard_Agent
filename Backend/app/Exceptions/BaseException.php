<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Class BaseException
 *
 * Abstract base exception that enriches Laravel's built-in Exception with
 * contextual data (e.g., user_id, machine_id, request payload) for richer
 * logging and debugging. All DeskGuard custom exceptions must extend this class.
 *
 * @package App\Exceptions
 */
abstract class BaseException extends Exception
{
    /**
     * Additional contextual data attached to the exception.
     *
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * BaseException constructor.
     *
     * @param string               $message  The human-readable exception message
     * @param int                  $code     The HTTP status code (default 500)
     * @param array<string, mixed> $context  Extra data for logging (user_id, machine_id, payload, etc.)
     */
    public function __construct(string $message, int $code = 500, array $context = [])
    {
        parent::__construct($message, $code);

        $this->context = $context;
    }

    /**
     * Retrieve the contextual data attached to this exception.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
