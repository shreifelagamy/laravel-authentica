<?php

namespace Shreifelagamy\Authentica\Testing;

use Shreifelagamy\Authentica\Enums\OtpMethod;

/**
 * A message the fake recorded, with the values the real client would have sent.
 */
final class SentMessage
{
    /**
     * @param  array<string, mixed>  $payload  the request body the real client would have sent
     */
    public function __construct(
        public readonly string $type,
        public readonly string $to,
        public readonly ?string $content,
        public readonly ?string $code,
        public readonly ?OtpMethod $method,
        public readonly int|string|null $template,
        public readonly ?string $sender,
        public readonly array $payload,
    ) {}

    /**
     * @param  array{phone: string, message: string, sender_name: ?string}  $payload
     */
    public static function sms(array $payload): self
    {
        return new self('sms', $payload['phone'], $payload['message'], null, null, null, $payload['sender_name'], $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function otp(array $payload): self
    {
        $to = $payload['phone'] ?? $payload['email'];
        $template = $payload['template_id'] ?? null;

        return new self(
            'otp',
            is_string($to) ? $to : '',
            null,
            isset($payload['otp']) && is_string($payload['otp']) ? $payload['otp'] : null,
            OtpMethod::from((string) $payload['method']),
            is_int($template) || is_string($template) ? $template : null,
            null,
            $payload,
        );
    }

    public function isSms(): bool
    {
        return $this->type === 'sms';
    }

    public function isOtp(): bool
    {
        return $this->type === 'otp';
    }
}
