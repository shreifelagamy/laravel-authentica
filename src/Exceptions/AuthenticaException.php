<?php

namespace Shreifelagamy\Authentica\Exceptions;

use RuntimeException;

/**
 * Base class of every exception the package throws.
 */
class AuthenticaException extends RuntimeException
{
    public static function missingApiKey(): self
    {
        return new self('The Authentica API key is not set. Add AUTHENTICA_API_KEY to your .env file.');
    }

    public static function unexpectedResponse(int $status, string $message): self
    {
        return new self("Authentica answered with HTTP {$status}: {$message}", $status);
    }
}
