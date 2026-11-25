<?php

namespace Shreifelagamy\Authentica\Http;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Shreifelagamy\Authentica\Exceptions\AuthenticaException;
use Shreifelagamy\Authentica\Exceptions\AuthenticaInvalidApiKeyException;
use Shreifelagamy\Authentica\Exceptions\AuthenticaRateLimitedException;
use Shreifelagamy\Authentica\Exceptions\AuthenticaRequestRejectedException;
use Shreifelagamy\Authentica\Exceptions\AuthenticaUnavailableException;
use Shreifelagamy\Authentica\Support\Mask;

/**
 * Sends requests to the Authentica API and turns error answers into exceptions. Requests are never retried: a resend
 * can deliver another message and spend credits.
 */
class Transport
{
    public const CLIENT_HEADER = 'laravel-authentica';

    public function __construct(
        private readonly Container $container,
        private readonly ?string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeout,
    ) {}

    /**
     * @throws AuthenticaException
     */
    public function get(string $path): ApiResponse
    {
        return $this->send(fn (PendingRequest $request): Response => $request->get($path));
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws AuthenticaException
     */
    public function post(string $path, array $payload): ApiResponse
    {
        $payload = array_filter($payload, fn (mixed $value): bool => $value !== null && $value !== '');

        return $this->send(fn (PendingRequest $request): Response => $request->post($path, $payload));
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     */
    private function send(callable $call): ApiResponse
    {
        try {
            $response = $call($this->request());
        } catch (ConnectionException $exception) {
            throw AuthenticaUnavailableException::connectionFailed($exception);
        }

        if ($response->failed()) {
            throw $this->exceptionFor($response);
        }

        $body = $response->json();

        return new ApiResponse($response->status(), is_array($body) ? $body : []);
    }

    private function request(): PendingRequest
    {
        if (blank($this->apiKey)) {
            throw AuthenticaException::missingApiKey();
        }

        // Resolved on each request, so Http::fake() and Http::swap() in tests always apply.
        return $this->container->make(Factory::class)
            ->baseUrl(rtrim($this->baseUrl, '/'))
            ->timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-Authorization' => $this->apiKey,
                'X-Authentica-Client' => self::CLIENT_HEADER,
            ]);
    }

    private function exceptionFor(Response $response): AuthenticaException
    {
        $message = Mask::phonesIn($this->errorMessage($response));

        return match (true) {
            $response->status() === 401 => AuthenticaInvalidApiKeyException::make($message),
            $response->status() === 422 => AuthenticaRequestRejectedException::make($message),
            $response->status() === 429 => AuthenticaRateLimitedException::make($message, $this->retryAfter($response)),
            $response->serverError() => AuthenticaUnavailableException::serverError($response->status(), $message),
            default => AuthenticaException::unexpectedResponse($response->status(), $message),
        };
    }

    private function errorMessage(Response $response): string
    {
        $message = $response->json('errors.0.message') ?? $response->json('message') ?? $response->json('error');

        return is_string($message) && $message !== '' ? $message : ($response->reason() ?: 'no message');
    }

    private function retryAfter(Response $response): ?int
    {
        $header = $response->header('Retry-After');

        return is_numeric($header) ? (int) $header : null;
    }
}
