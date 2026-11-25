<?php

namespace Shreifelagamy\Authentica\Exceptions;

/**
 * Too many requests (HTTP 429). retryAfter() gives the seconds to wait when Authentica says so.
 */
class AuthenticaRateLimitedException extends AuthenticaException
{
    protected ?int $retryAfter = null;

    public static function make(string $message, ?int $retryAfter): self
    {
        $exception = new self("Authentica is rate limiting requests: {$message}", 429);
        $exception->retryAfter = $retryAfter;

        return $exception;
    }

    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
