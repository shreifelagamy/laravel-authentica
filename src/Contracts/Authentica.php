<?php

namespace Shreifelagamy\Authentica\Contracts;

use Shreifelagamy\Authentica\Enums\OtpMethod;
use Shreifelagamy\Authentica\Exceptions\AuthenticaException;
use Shreifelagamy\Authentica\Http\ApiResponse;

/**
 * What the Authentica client offers. Implemented by the real client and by the fake used in tests.
 */
interface Authentica
{
    /**
     * Sends a custom SMS. The sender name must be approved in Authentica.
     *
     * @throws AuthenticaException
     */
    public function sendSms(string $phone, string $message, ?string $sender = null): ApiResponse;

    /**
     * Sends a one-time code by SMS, WhatsApp or email. Pass $code to send your own code (digits only); leave it
     * empty to let Authentica create one, then check it with verifyOtp().
     *
     * @param  string  $recipient  a phone number, or an email address for the email method
     *
     * @throws AuthenticaException
     */
    public function sendOtp(
        string $recipient,
        ?string $code = null,
        OtpMethod|string|null $method = null,
        int|string|null $template = null,
        ?string $fallbackPhone = null,
        ?string $fallbackEmail = null,
    ): ApiResponse;

    /**
     * Checks a code Authentica created. Returns false for a wrong or expired code.
     *
     * @param  string  $recipient  the phone number or email address the code was sent to
     *
     * @throws AuthenticaException for anything other than a wrong or expired code
     */
    public function verifyOtp(string $recipient, string $code): bool;

    /**
     * The credits left on the account.
     *
     * @throws AuthenticaException
     */
    public function balance(): int;
}
