<?php

namespace Shreifelagamy\Authentica\Exceptions;

use Throwable;

/**
 * Authentica could not be reached, timed out or had a server error (HTTP 5xx).
 */
class AuthenticaUnavailableException extends AuthenticaException
{
    public static function serverError(int $status, string $message): self
    {
        return new self("Authentica is unavailable (HTTP {$status}): {$message}", $status);
    }

    public static function connectionFailed(Throwable $previous): self
    {
        return new self('Could not reach Authentica: '.$previous->getMessage(), 0, $previous);
    }
}
