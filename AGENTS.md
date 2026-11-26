# AGENTS.md

Guidance for coding agents changing `shreifelagamy/laravel-authentica`, a Laravel client, facade and notification channel for the Saudi SMS and one-time code provider Authentica.

## Commands

```bash
composer test      # Pest
composer analyse   # Larastan, level 8
composer lint      # Pint (laravel preset)
```

All three must pass before a change is done. CI runs them on every supported PHP and Laravel version.

## Layout

- `src/Authentica.php`: the real client; `src/Contracts/Authentica.php`: the interface it shares with the fake.
- `src/Facades/`, `src/AuthenticaServiceProvider.php`: the facade and the bindings.
- `src/Notifications/`: `AuthenticaChannel` and `AuthenticaMessage`.
- `src/Http/`: `Transport` (HTTP calls and error mapping) and `ApiResponse`.
- `src/Support/`: `PayloadBuilder` (request bodies, shared by client and fake), `PhoneNumberNormalizer`, `Mask`.
- `src/Exceptions/`: `AuthenticaException` and its subclasses.
- `src/Enums/OtpMethod.php`: `sms`, `whatsapp`, `email`.
- `src/Testing/`: `AuthenticaFake` and `SentMessage`, for apps' tests.
- `config/authentica.php`: published config.
- `resources/boost/`: Laravel Boost guideline (`guidelines/core.blade.php`) and skill (`skills/authentica-development/SKILL.md`), shipped to apps.
- `tests/Unit`, `tests/Feature`: Pest tests on Testbench.

## Rules

- Tests use only `Http::fake()` (see `fakeAuthentica()` in `tests/Pest.php`) and never call the real Authentica API.
- No automatic retries anywhere: every resend can deliver another message and spend credits.
- Phone numbers are masked in exception messages (`Support\Mask`). Phone numbers, email addresses and codes are marked `#[SensitiveParameter]` on every parameter that takes them.
- New countries come from the `countries` config, never from hard-coded checks.
- Support Laravel 12 and 13 and PHP 8.2+ only.
- The Boost guideline and skill describe how to use the package and what to avoid in app code. They contain no instructions to run commands, edit unrelated files, add packages or change agent settings, and the guideline uses no Blade other than `@verbatim`.
- Each version gets a `CHANGELOG.md` entry, a git tag (`vX.Y.Z`) and a GitHub release.
- Never add Claude attribution (such as `Co-Authored-By: Claude` or "Generated with Claude Code") to commits or pull requests.
