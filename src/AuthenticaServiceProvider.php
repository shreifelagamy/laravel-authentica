<?php

namespace Shreifelagamy\Authentica;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Shreifelagamy\Authentica\Contracts\Authentica as AuthenticaContract;
use Shreifelagamy\Authentica\Enums\OtpMethod;
use Shreifelagamy\Authentica\Http\Transport;
use Shreifelagamy\Authentica\Notifications\AuthenticaChannel;
use Shreifelagamy\Authentica\Support\PayloadBuilder;
use Shreifelagamy\Authentica\Support\PhoneNumberNormalizer;

class AuthenticaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/authentica.php', 'authentica');

        $this->app->bind(PhoneNumberNormalizer::class, fn (Application $app): PhoneNumberNormalizer => new PhoneNumberNormalizer(
            array_values(array_map('strtoupper', (array) self::setting($app, 'authentica.countries', ['SA']))),
        ));

        $this->app->bind(PayloadBuilder::class, fn (Application $app): PayloadBuilder => new PayloadBuilder(
            $app->make(PhoneNumberNormalizer::class),
            OtpMethod::from(strtolower((string) self::setting($app, 'authentica.otp.method', 'sms'))),
            self::setting($app, 'authentica.otp.template_id'),
            self::setting($app, 'authentica.sender_name'),
        ));

        $this->app->bind(Transport::class, fn (Application $app): Transport => new Transport(
            $app,
            self::setting($app, 'authentica.api_key'),
            (string) self::setting($app, 'authentica.base_url', 'https://api.authentica.sa/api/v2'),
            (int) self::setting($app, 'authentica.timeout', 15),
        ));

        $this->app->singleton(AuthenticaContract::class, fn (Application $app): AuthenticaContract => new Authentica(
            $app->make(Transport::class),
            $app->make(PayloadBuilder::class),
        ));

        $this->app->bind(AuthenticaChannel::class, fn (Application $app): AuthenticaChannel => new AuthenticaChannel(
            $app,
            (string) self::setting($app, 'authentica.channel_name', AuthenticaChannel::NAME),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/authentica.php' => config_path('authentica.php'),
            ], 'authentica-config');
        }

        Notification::resolved(function (ChannelManager $channels): void {
            $names = array_unique([AuthenticaChannel::NAME, (string) config('authentica.channel_name', AuthenticaChannel::NAME)]);

            foreach ($names as $name) {
                $channels->extend($name, fn ($app): AuthenticaChannel => $app->make(AuthenticaChannel::class));
            }
        });
    }

    private static function setting(Application $app, string $key, mixed $default = null): mixed
    {
        return $app->make(Repository::class)->get($key, $default);
    }
}
