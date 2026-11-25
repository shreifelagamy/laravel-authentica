<?php

namespace Shreifelagamy\Authentica\Exceptions;

/**
 * Authentica rejected the request (HTTP 422), for example a wrong template or a refused number.
 */
class AuthenticaRequestRejectedException extends AuthenticaException
{
    public static function make(string $message): self
    {
        return new self("Authentica rejected the request: {$message}", 422);
    }
}
