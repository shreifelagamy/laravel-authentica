<?php

namespace Shreifelagamy\Authentica;

use Shreifelagamy\Authentica\Contracts\Authentica as AuthenticaContract;
use Shreifelagamy\Authentica\Enums\OtpMethod;
use Shreifelagamy\Authentica\Exceptions\AuthenticaRequestRejectedException;
use Shreifelagamy\Authentica\Http\ApiResponse;
use Shreifelagamy\Authentica\Http\Transport;
use Shreifelagamy\Authentica\Support\PayloadBuilder;

/**
 * The Authentica API client (https://docs.authentica.sa).
 */
class Authentica implements AuthenticaContract
{
    public function __construct(
        private readonly Transport $transport,
        private readonly PayloadBuilder $payloads,
    ) {}

    public function sendSms(string $phone, string $message, ?string $sender = null): ApiResponse
    {
        return $this->transport->post('/send-sms', $this->payloads->sms($phone, $message, $sender));
    }

    public function sendOtp(
        string $recipient,
        ?string $code = null,
        OtpMethod|string|null $method = null,
        int|string|null $template = null,
        ?string $fallbackPhone = null,
        ?string $fallbackEmail = null,
    ): ApiResponse {
        return $this->transport->post(
            '/send-otp',
            $this->payloads->otp($recipient, $code, $method, $template, $fallbackPhone, $fallbackEmail),
        );
    }

    public function verifyOtp(string $recipient, string $code): bool
    {
        try {
            return $this->transport->post('/verify-otp', $this->payloads->verify($recipient, $code))->successful();
        } catch (AuthenticaRequestRejectedException) {
            return false;
        }
    }

    public function balance(): int
    {
        $balance = $this->transport->get('/balance')->data('balance', 0);

        return is_numeric($balance) ? (int) $balance : 0;
    }
}
