# Changelog

## v0.1.0 - 2026-11-25

- Client and `Authentica` facade: `sendSms()`, `sendOtp()` (SMS, WhatsApp or email, with your own code or Authentica's), `verifyOtp()` and `balance()`.
- `authentica` notification channel with `AuthenticaMessage::sms()` and `AuthenticaMessage::otp()`, `toAuthentica()` or `toSms()`, on-demand routing and a configurable extra channel name.
- Mobile numbers checked and turned into international format with `propaganistas/laravel-phone`, for the countries in `authentica.countries` (Saudi Arabia by default).
- Typed exceptions with phone numbers masked in their messages.
- `Authentica::fake()` with assertions for tests.
