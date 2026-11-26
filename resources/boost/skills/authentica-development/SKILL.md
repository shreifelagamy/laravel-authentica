---
name: authentica-development
description: Send SMS messages and one-time codes (SMS, WhatsApp or email) with shreifelagamy/laravel-authentica. Use when writing sign-in or verification code flows, Authentica notifications, choosing who receives them, handling Authentica exceptions, or testing with Authentica::fake().
---

# Authentica development

`shreifelagamy/laravel-authentica` talks to Authentica, a Saudi SMS and one-time code provider. Everything goes through the `Shreifelagamy\Authentica\Facades\Authentica` facade or the `authentica` notification channel.

## Never retry a send

Every request can deliver a message and spend credits, even one that timed out. The package never retries, and app code should not either: no loops, no `retry()`, no `$tries` above 1, no `backoff`. If a send fails, let it fail and let the user ask for a new code.

## Sign-in with your own code

Generate the code in the app, store a hash of it, and send it as a queued, encrypted notification tried once. `ShouldBeEncrypted` keeps the code out of the queue payload in plain text, and `#[SensitiveParameter]` keeps it out of stack traces (failed jobs, logs, error trackers).

```php
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use SensitiveParameter;
use Shreifelagamy\Authentica\Notifications\AuthenticaMessage;

class SignInCode extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(#[SensitiveParameter] public readonly string $code) {}

    public function via(object $notifiable): array
    {
        return ['authentica'];
    }

    public function toAuthentica(object $notifiable): AuthenticaMessage
    {
        return AuthenticaMessage::otp($this->code)->via('sms');
    }
}
```

The code must be digits only, such as `(string) random_int(100000, 999999)`; anything else throws `InvalidArgumentException`. When you send your own code, the app checks it; do not call `verifyOtp()` for it.

To let Authentica create and check the code instead, leave the code out and call `Authentica::verifyOtp($phone, $input)` later. It returns `false` for a wrong or expired code and throws only for other problems.

## WhatsApp with an SMS fallback

```php
use Shreifelagamy\Authentica\Enums\OtpMethod;
use Shreifelagamy\Authentica\Facades\Authentica;

Authentica::sendOtp('0551234567', method: OtpMethod::Whatsapp, fallbackPhone: '0551234567');

// In a notification
AuthenticaMessage::otp($this->code)->via('whatsapp')->fallbackPhone($notifiable->mobile);
```

For email codes, `method: OtpMethod::Email` (or `->via('email')`) with an email address as the recipient; `fallbackEmail` works the same way as `fallbackPhone`.

## Templates

Leave the template empty to use the method's default template in the Authentica dashboard, or set `AUTHENTICA_OTP_TEMPLATE_ID` for an app-wide default. When you pass one (`template: 31` or `->template(31)`), it must be a template of that method: an SMS template for `sms`, a WhatsApp template for `whatsapp`. A mismatch is rejected by Authentica.

## Who receives a notification

For SMS and WhatsApp, the channel takes the first of:

1. `->to('0551234567')` on the message;
2. `routeNotificationForAuthentica()` on the notifiable (or `routeNotificationFor()` for the configured channel name);
3. the notifiable's `phone`, `mobile` or `phone_number` attribute.

Email codes go to `routeNotificationForMail()` or the `email` attribute.

```php
public function routeNotificationForAuthentica(Notification $notification): ?string
{
    return $this->mobile;
}
```

Without a model, use `Notification::route('authentica', '0551234567')->notify(new SignInCode($code));`. When no recipient is found, the channel throws `AuthenticaMissingRecipientException`, so a queued notification fails visibly.

## Provider-neutral notifications

- If the notification has no `toAuthentica()`, the channel calls `toSms()`. Both may return an `AuthenticaMessage`, a plain string (sent as an SMS) or `null` (nothing is sent).
- The `channel_name` config (`AUTHENTICA_CHANNEL_NAME`) adds another name for the channel, such as `sms`, so `via()` can return `['sms']`. The channel stays available as `authentica` and as `AuthenticaChannel::class`.

## Phone numbers

Numbers may be written the way people type them. For Saudi Arabia, `0551234567`, `551234567`, `055 123 4567`, `966551234567`, `00966551234567`, `+966551234567` and Arabic digits all become `+966551234567`. Only mobile numbers of the countries in `config('authentica.countries')` (default `['SA']`) are accepted; local numbers are read as numbers of those countries, in order. Other countries are added there, for example `['SA', 'AE']`. In form requests, validate with laravel-phone's rule: `'mobile' => ['required', 'phone:SA,mobile']`.

## Exceptions

All extend `Shreifelagamy\Authentica\Exceptions\AuthenticaException`. Phone numbers are masked in their messages.

| Exception | When |
|---|---|
| `AuthenticaInvalidPhoneException` | not a mobile number of an allowed country; thrown before any request, so no credit is spent |
| `AuthenticaMissingRecipientException` | a notification has no phone number or email to send to |
| `AuthenticaInvalidApiKeyException` | HTTP 401: the API key is wrong |
| `AuthenticaRequestRejectedException` | HTTP 422: Authentica rejected the request (for example a template of the wrong method) |
| `AuthenticaRateLimitedException` | HTTP 429; `retryAfter()` gives the seconds to wait when Authentica says |
| `AuthenticaUnavailableException` | HTTP 5xx, a timeout or no connection; the message may still have been delivered |
| `AuthenticaException` | no API key configured, on the first request |

`InvalidArgumentException` is thrown for a code that is not digits only, an unknown method, an invalid email address or an empty SMS.

On a rate limit, tell the user to wait `retryAfter()` seconds and ask again; do not schedule a resend.

## Testing

`Authentica::fake()` replaces the client, records what would have been sent and checks numbers like the real client. It also covers notifications.

```php
use Shreifelagamy\Authentica\Facades\Authentica;
use Shreifelagamy\Authentica\Testing\SentMessage;

$authentica = Authentica::fake();

// ... run the code under test ...

$authentica->assertOtpSent(fn (SentMessage $otp) => $otp->to === '+966551234567' && $otp->code === '482913');
$authentica->assertSmsSent(fn (SentMessage $sms) => str_contains($sms->content, 'confirmed'), times: 1);
$authentica->assertSentTo('0551234567');
$authentica->assertSentCount(2);
$authentica->assertNothingSent();
$authentica->assertSmsNotSent();
$authentica->assertOtpNotSent();
```

`SentMessage` has `type`, `to` (normalised, `+966…`), `content`, `code`, `method`, `template`, `sender` and `payload`. On the fake, `verifyOtp()` accepts the last code sent to the number, or `000000` when Authentica would have created it; `verifyUsing(fn ($recipient, $code) => …)` changes that, and `setBalance()` sets what `balance()` returns.

A queued notification runs at once under the `sync` queue in tests. With `Notification::fake()` instead, nothing reaches Authentica; assert with `Notification::assertSentTo()`.
