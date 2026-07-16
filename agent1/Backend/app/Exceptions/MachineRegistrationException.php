<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Class MachineRegistrationException
 *
 * Thrown when a machine registration attempt fails. Common scenarios include
 * invalid or expired activation tokens, duplicate registration, or network
 * validation errors during the handshake process.
 *
 * @package App\Exceptions
 */
class MachineRegistrationException extends BaseException
{
    /**
     * MachineRegistrationException constructor.
     *
     * @param string               $message  A description of the registration failure
     * @param int                  $code     HTTP status code (default 422 Unprocessable Entity)
     * @param array<string, mixed> $context  Contextual data (token, machine_uid, etc.)
     */
    public function __construct(string $message, int $code = 422, array $context = [])
    {
        parent::__construct($message, $code, $context);
    }
}
