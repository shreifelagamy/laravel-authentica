<?php

namespace Shreifelagamy\Authentica\Facades;

use Illuminate\Support\Facades\Facade;
use Shreifelagamy\Authentica\Contracts\Authentica as AuthenticaContract;
use Shreifelagamy\Authentica\Enums\OtpMethod;
use Shreifelagamy\Authentica\Http\ApiResponse;
use Shreifelagamy\Authentica\Support\PayloadBuilder;
use Shreifelagamy\Authentica\Testing\AuthenticaFake;

/**
 * @method static ApiResponse sendSms(string $phone, string $message, ?string $sender = null)
 * @method static ApiResponse sendOtp(string $recipient, ?string $code = null, OtpMethod|string|null $method = null, int|string|null $template = null, ?string $fallbackPhone = null, ?string $fallbackEmail = null)
 * @method static bool verifyOtp(string $recipient, string $code)
 * @method static int balance()
 * @method static void assertSmsSent(\Closure|null $filter = null, ?int $times = null)
 * @method static void assertSmsNotSent(\Closure|null $filter = null)
 * @method static void assertOtpSent(\Closure|null $filter = null, ?int $times = null)
 * @method static void assertOtpNotSent(\Closure|null $filter = null)
 * @method static void assertSentTo(string $recipient)
 * @method static void assertSentCount(int $count)
 * @method static void assertNothingSent()
 *
 * @see \Shreifelagamy\Authentica\Authentica
 * @see AuthenticaFake
 */
class Authentica extends Facade
{
    /**
     * Replaces the client with a fake that records messages instead of sending them.
     */
    public static function fake(): AuthenticaFake
    {
        $fake = new AuthenticaFake(app(PayloadBuilder::class));

        static::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return AuthenticaContract::class;
    }
}
