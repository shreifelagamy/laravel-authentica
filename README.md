# Authentica for Laravel

Send SMS messages and one-time codes (by SMS, WhatsApp or email) through [Authentica](https://authentica.sa) from Laravel: directly with a facade, or with Laravel notifications.

> This is a community package. It is not made or supported by Authentica.

- `Authentica::sendSms()`, `sendOtp()`, `verifyOtp()` and `balance()`
- A notification channel for SMS and one-time codes
- Mobile numbers checked and formatted with [laravel-phone](https://github.com/Propaganistas/Laravel-Phone) before anything is sent, so no credit is spent on a bad number
- Typed exceptions, with phone numbers masked in their messages
- `Authentica::fake()` with assertions for your tests

Requires PHP 8.2+ and Laravel 11, 12 or 13.

## Installation

```bash
composer require shreifelagamy/laravel-authentica
```

Add your API key from the [Authentica portal](https://portal.authentica.sa) to `.env`:

```dotenv
AUTHENTICA_API_KEY=your-api-key

# Optional
AUTHENTICA_OTP_METHOD=sms          # sms, whatsapp or email
AUTHENTICA_OTP_TEMPLATE_ID=        # empty: the method's default template in the dashboard
AUTHENTICA_SENDER_NAME=            # your approved SMS sender name
AUTHENTICA_TIMEOUT=15
```

To change the allowed countries or the channel name, publish the config:

```bash
php artisan vendor:publish --tag=authentica-config
```

## Phone numbers

Numbers can be written the way people type them. For Saudi Arabia, `0551234567`, `551234567`, `055 123 4567`, `966551234567`, `00966551234567`, `+966551234567` and Arabic digits (`٠٥٥١٢٣٤٥٦٧`) all become `+966551234567`.

Only mobile numbers of the countries in `config('authentica.countries')` are accepted. That is `['SA']` by default. Anything else throws `AuthenticaInvalidPhoneException` before a request is made.

```php
// config/authentica.php
'countries' => ['SA', 'AE'],
```

Whether Authentica delivers SMS outside Saudi Arabia, and at what price, depends on your Authentica account.

To validate numbers in your forms, use laravel-phone's rule:

```php
$request->validate(['mobile' => ['required', 'phone:SA,mobile']]);
```

## Using the facade

```php
use Shreifelagamy\Authentica\Enums\OtpMethod;
use Shreifelagamy\Authentica\Facades\Authentica;

// A custom SMS. The sender name must be approved in Authentica.
Authentica::sendSms('0551234567', 'Your booking is confirmed');
Authentica::sendSms('0551234567', 'Your booking is confirmed', sender: 'MyBrand');

// A one-time code Authentica creates and checks
Authentica::sendOtp('0551234567');
Authentica::verifyOtp('0551234567', $request->code); // true or false

// Your own code (digits only), checked by your app
Authentica::sendOtp('0551234567', code: '482913');

// By WhatsApp, with a template and an SMS fallback
Authentica::sendOtp('0551234567', method: OtpMethod::Whatsapp, template: 31, fallbackPhone: '0551234567');

// By email
Authentica::sendOtp('user@example.com', method: OtpMethod::Email);

// Credits left
Authentica::balance();
```

`verifyOtp()` returns `false` for a wrong or expired code. Other problems throw an exception.

Requests are never retried automatically: Authentica warns that every resend can deliver another message and spend credits.

## Using notifications

Return `authentica` from `via()` and build the message in `toAuthentica()`:

```php
use Illuminate\Notifications\Notification;
use Shreifelagamy\Authentica\Notifications\AuthenticaMessage;

class BookingConfirmed extends Notification
{
    public function via(object $notifiable): array
    {
        return ['authentica'];
    }

    public function toAuthentica(object $notifiable): AuthenticaMessage
    {
        return AuthenticaMessage::sms('Your booking is confirmed')->sender('MyBrand');
    }
}
```

A one-time code:

```php
public function toAuthentica(object $notifiable): AuthenticaMessage
{
    return AuthenticaMessage::otp($this->code)   // leave empty to let Authentica create it
        ->via('whatsapp')                         // sms, whatsapp or email
        ->template(31);
}
```

`toAuthentica()` may also return a plain string for an SMS, or `null` to send nothing.

### Who receives it

The channel looks for the number in this order:

1. `->to('0551234567')` on the message
2. a `routeNotificationForAuthentica()` method on the notifiable
3. a `phone`, `mobile` or `phone_number` attribute

Email codes go to `routeNotificationForMail()` or the `email` attribute.

```php
public function routeNotificationForAuthentica(Notification $notification): ?string
{
    return $this->mobile;
}
```

Without a model:

```php
Notification::route('authentica', '0551234567')->notify(new BookingConfirmed);
```

If there is no number, the channel throws `AuthenticaMissingRecipientException`, so a queued notification fails visibly.

### Provider-neutral notifications

To be able to switch SMS providers later without touching your notifications:

- name the method `toSms()`. The channel uses it when there is no `toAuthentica()`.
- set an extra channel name, so `via()` can return `'sms'`:

```php
// config/authentica.php
'channel_name' => 'sms',
```

The channel stays available as `authentica` and as `AuthenticaChannel::class`.

## Errors

Every exception extends `Shreifelagamy\Authentica\Exceptions\AuthenticaException`.

| Exception | When |
|---|---|
| `AuthenticaInvalidApiKeyException` | HTTP 401: the API key is wrong |
| `AuthenticaRequestRejectedException` | HTTP 422: the request was rejected |
| `AuthenticaRateLimitedException` | HTTP 429. `retryAfter()` gives the seconds to wait, when Authentica says |
| `AuthenticaUnavailableException` | HTTP 5xx, a timeout or no connection |
| `AuthenticaInvalidPhoneException` | not a valid mobile number of an allowed country (no request is made) |
| `AuthenticaMissingRecipientException` | a notification has no one to send to |

A missing API key throws `AuthenticaException` when the first request is made.

## Testing

```php
use Shreifelagamy\Authentica\Facades\Authentica;
use Shreifelagamy\Authentica\Testing\SentMessage;

$authentica = Authentica::fake();

// ... run your code ...

$authentica->assertSmsSent(fn (SentMessage $sms) => $sms->to === '+966551234567' && str_contains($sms->content, 'confirmed'));
$authentica->assertOtpSent(fn (SentMessage $otp) => $otp->code === '482913', times: 1);
$authentica->assertSentTo('0551234567');
$authentica->assertSentCount(2);
$authentica->assertNothingSent();
```

The fake checks numbers like the real client. `verifyOtp()` on the fake accepts the last code sent to the number (`000000` when Authentica would have created it). `verifyUsing()` changes that, and `setBalance()` sets the balance.

To run the package's own tests:

```bash
composer test
composer analyse
composer lint
```

## License

MIT. See [LICENSE.md](LICENSE.md).
