<?php

namespace Shreifelagamy\Authentica\Support;

use InvalidArgumentException;
use SensitiveParameter;
use Shreifelagamy\Authentica\Enums\OtpMethod;
use Shreifelagamy\Authentica\Exceptions\AuthenticaInvalidPhoneException;

/**
 * Builds the request bodies of the Authentica API from the package's arguments and config. The real client and the
 * fake both use it, so tests check the same numbers and defaults production sends.
 */
final class PayloadBuilder
{
    public function __construct(
        private readonly PhoneNumberNormalizer $phones,
        private readonly OtpMethod $defaultMethod,
        private readonly int|string|null $defaultTemplate,
        private readonly ?string $defaultSender,
    ) {}

    /**
     * @return array{phone: string, message: string, sender_name: ?string}
     *
     * @throws AuthenticaInvalidPhoneException
     */
    public function sms(#[SensitiveParameter] string $phone, string $message, ?string $sender = null): array
    {
        if (trim($message) === '') {
            throw new InvalidArgumentException('An Authentica SMS needs a message.');
        }

        return [
            'phone' => $this->phones->normalize($phone),
            'message' => $message,
            'sender_name' => $this->filled($sender) ?? $this->filled($this->defaultSender),
        ];
    }

    /**
     * @return array{method: string, phone?: string, email?: string, otp: ?string, template_id: int|string|null, fallback_phone: ?string, fallback_email: ?string}
     *
     * @throws AuthenticaInvalidPhoneException
     */
    public function otp(
        #[SensitiveParameter] string $recipient,
        #[SensitiveParameter] ?string $code = null,
        OtpMethod|string|null $method = null,
        int|string|null $template = null,
        #[SensitiveParameter] ?string $fallbackPhone = null,
        #[SensitiveParameter] ?string $fallbackEmail = null,
    ): array {
        $method = $this->method($method);

        if ($code !== null && preg_match('/^\d+$/', $code) !== 1) {
            throw new InvalidArgumentException('An Authentica one-time code must contain digits only.');
        }

        return [
            'method' => $method->value,
            ...$this->recipient($recipient, $method),
            'otp' => $code,
            'template_id' => $template ?? $this->filled($this->defaultTemplate),
            'fallback_phone' => $fallbackPhone === null ? null : $this->phones->normalize($fallbackPhone),
            'fallback_email' => $fallbackEmail === null ? null : $this->email($fallbackEmail),
        ];
    }

    /**
     * @return array{otp: string, phone?: string, email?: string}
     *
     * @throws AuthenticaInvalidPhoneException
     */
    public function verify(#[SensitiveParameter] string $recipient, #[SensitiveParameter] string $code): array
    {
        $method = str_contains($recipient, '@') ? OtpMethod::Email : OtpMethod::Sms;

        return ['otp' => $code, ...$this->recipient($recipient, $method)];
    }

    public function method(OtpMethod|string|null $method): OtpMethod
    {
        if ($method instanceof OtpMethod) {
            return $method;
        }

        if ($method === null) {
            return $this->defaultMethod;
        }

        return OtpMethod::tryFrom(strtolower($method))
            ?? throw new InvalidArgumentException("Unknown Authentica method [{$method}]. Use sms, whatsapp or email.");
    }

    /**
     * @return array{phone: string}|array{email: string}
     */
    private function recipient(#[SensitiveParameter] string $recipient, OtpMethod $method): array
    {
        return $method->usesPhone()
            ? ['phone' => $this->phones->normalize($recipient)]
            : ['email' => $this->email($recipient)];
    }

    private function email(#[SensitiveParameter] string $email): string
    {
        $email = trim($email);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Authentica needs a valid email address for the email method.');
        }

        return $email;
    }

    /**
     * @template T of int|string|null
     *
     * @param  T  $value
     * @return T|null
     */
    private function filled(int|string|null $value): int|string|null
    {
        return $value === null || $value === '' ? null : $value;
    }
}
