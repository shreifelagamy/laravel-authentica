<?php

namespace Shreifelagamy\Authentica\Http;

/**
 * A successful answer from Authentica. Hides that the API marks success as "success" on some endpoints and "status"
 * on others.
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $body
     */
    public function __construct(
        public readonly int $status,
        public readonly array $body,
    ) {}

    public function successful(): bool
    {
        return ($this->body['success'] ?? $this->body['status'] ?? false) === true;
    }

    public function message(): ?string
    {
        $message = $this->body['message'] ?? null;

        return is_string($message) ? $message : null;
    }

    public function data(?string $key = null, mixed $default = null): mixed
    {
        $data = $this->body['data'] ?? null;

        return $key === null ? ($data ?? $default) : data_get($data, $key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->body;
    }
}
