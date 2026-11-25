<?php

namespace Shreifelagamy\Authentica\Exceptions;

/**
 * Authentica refused the API key (HTTP 401).
 */
class AuthenticaInvalidApiKeyException extends AuthenticaException
{
    public static function make(string $message): self
    {
        return new self("Authentica refused the API key: {$message}", 401);
    }
}
