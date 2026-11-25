<?php

namespace Shreifelagamy\Authentica\Notifications;

use Shreifelagamy\Authentica\Enums\OtpMethod;

/**
 * What a notification sends through Authentica: a custom SMS (sms()) or a one-time code (otp()) by SMS, WhatsApp or
 * email.
 */
class AuthenticaMessage
{
    public const TYPE_SMS = 'sms';

    public const TYPE_OTP = 'otp';

    public ?string $sender = null;

    public OtpMethod|string|null $method = null;

    public int|string|null $template = null;

    public ?string $fallbackPhone = null;

    public ?string $fallbackEmail = null;

    public ?string $to = null;

    final public function __construct(
        public string $type = self::TYPE_SMS,
        public string $content = '',
        public ?string $code = null,
    ) {}

    public static function sms(string $content = ''): static
    {
        return new static(self::TYPE_SMS, $content);
    }

    /**
     * A one-time code. Leave $code empty to let Authentica create it.
     */
    public static function otp(?string $code = null): static
    {
        return new static(self::TYPE_OTP, code: $code);
    }

    public function content(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * The approved sender name of an SMS.
     */
    public function sender(string $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * How a one-time code is delivered.
     */
    public function via(OtpMethod|string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function template(int|string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function fallbackPhone(string $phone): static
    {
        $this->fallbackPhone = $phone;

        return $this;
    }

    public function fallbackEmail(string $email): static
    {
        $this->fallbackEmail = $email;

        return $this;
    }

    /**
     * Sends to this phone number (or email address) instead of the notifiable's own.
     */
    public function to(string $recipient): static
    {
        $this->to = $recipient;

        return $this;
    }

    public function isOtp(): bool
    {
        return $this->type === self::TYPE_OTP;
    }
}
