## Authentica (shreifelagamy/laravel-authentica)

This package sends SMS messages and one-time codes (by SMS, WhatsApp or email) through Authentica, a Saudi provider. It offers the `Authentica` facade and an `authentica` notification channel. Phone numbers are checked and turned into international format (`+966…`) before any request; numbers outside the configured countries throw `AuthenticaInvalidPhoneException`. All exceptions extend `Shreifelagamy\Authentica\Exceptions\AuthenticaException`. For detailed flows, recipients, exceptions and testing, use the `authentica-development` skill.

### Facade

@verbatim
<code-snippet name="Sending and checking with the Authentica facade" lang="php">
use Shreifelagamy\Authentica\Facades\Authentica;

Authentica::sendSms('0551234567', 'Your booking is confirmed');
Authentica::sendOtp('0551234567');                       // Authentica creates the code
Authentica::sendOtp('0551234567', code: '482913');       // your own code, checked by your app
Authentica::verifyOtp('0551234567', $request->code);     // true, or false for a wrong or expired code
Authentica::balance();                                   // credits left, as an int
</code-snippet>
@endverbatim

### Notification channel

@verbatim
<code-snippet name="A notification sent through the authentica channel" lang="php">
use Shreifelagamy\Authentica\Notifications\AuthenticaMessage;

public function via(object $notifiable): array
{
    return ['authentica'];
}

public function toAuthentica(object $notifiable): AuthenticaMessage
{
    return AuthenticaMessage::otp($this->code)->via('sms');
}
</code-snippet>
@endverbatim

### Rules

- Never retry a send: not in a loop, not with `retry()`, not with several queue tries. Every resend can deliver another message and spend credits. Queued notifications that send through Authentica should set `$tries = 1`.
- One-time codes are digits only (`'482913'`). Any other character throws `InvalidArgumentException`.
- The template must match the method: an SMS template for `sms`, a WhatsApp template for `whatsapp`. Leave the template out to use the method's default template.
