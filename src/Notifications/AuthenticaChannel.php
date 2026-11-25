<?php

namespace Shreifelagamy\Authentica\Notifications;

use Illuminate\Contracts\Container\Container;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use LogicException;
use Shreifelagamy\Authentica\Contracts\Authentica;
use Shreifelagamy\Authentica\Enums\OtpMethod;
use Shreifelagamy\Authentica\Exceptions\AuthenticaMissingRecipientException;
use Shreifelagamy\Authentica\Http\ApiResponse;

/**
 * Sends notifications through Authentica. A notification returns "authentica" (or the configured channel name, or
 * this class) from via() and builds its message in toAuthentica(), or toSms() to stay provider-neutral.
 */
class AuthenticaChannel
{
    public const NAME = 'authentica';

    public function __construct(
        private readonly Container $container,
        private readonly string $channelName = self::NAME,
    ) {}

    public function send(object $notifiable, Notification $notification): ?ApiResponse
    {
        $message = $this->message($notifiable, $notification);

        if ($message === null) {
            return null;
        }

        $recipient = $message->to ?? $this->recipient($notifiable, $notification, $message);

        if (blank($recipient)) {
            throw AuthenticaMissingRecipientException::for($notifiable, $notification::class);
        }

        /** @var Authentica $authentica resolved on each send, so Authentica::fake() also covers channels made earlier */
        $authentica = $this->container->make(Authentica::class);

        if ($message->isOtp()) {
            return $authentica->sendOtp(
                $recipient,
                $message->code,
                $message->method,
                $message->template,
                $message->fallbackPhone,
                $message->fallbackEmail,
            );
        }

        return $authentica->sendSms($recipient, $message->content, $message->sender);
    }

    private function message(object $notifiable, Notification $notification): ?AuthenticaMessage
    {
        $method = match (true) {
            method_exists($notification, 'toAuthentica') => 'toAuthentica',
            method_exists($notification, 'toSms') => 'toSms',
            default => throw new LogicException(sprintf(
                '%s is sent through Authentica but has no toAuthentica() or toSms() method.',
                $notification::class,
            )),
        };

        $message = $notification->{$method}($notifiable);

        return match (true) {
            $message === null => null,
            $message instanceof AuthenticaMessage => $message,
            is_string($message) => AuthenticaMessage::sms($message),
            default => throw new LogicException(sprintf(
                '%s::%s() must return an AuthenticaMessage, a string or null.',
                $notification::class,
                $method,
            )),
        };
    }

    private function recipient(object $notifiable, Notification $notification, AuthenticaMessage $message): ?string
    {
        $usesEmail = $message->isOtp() && $this->usesEmail($message);

        $routes = $usesEmail ? ['mail'] : array_unique([$this->channelName, self::NAME, self::class]);

        foreach ($routes as $route) {
            if (method_exists($notifiable, 'routeNotificationFor')) {
                $recipient = $notifiable->routeNotificationFor($route, $notification);

                if (filled($recipient)) {
                    return (string) $recipient;
                }
            }
        }

        if ($notifiable instanceof AnonymousNotifiable) {
            return null;
        }

        $attributes = $usesEmail ? ['email'] : ['phone', 'mobile', 'phone_number'];

        foreach ($attributes as $attribute) {
            $value = $notifiable->{$attribute} ?? null;

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function usesEmail(AuthenticaMessage $message): bool
    {
        $method = $message->method instanceof OtpMethod
            ? $message->method
            : OtpMethod::tryFrom(strtolower((string) ($message->method ?? config('authentica.otp.method', 'sms'))));

        return $method === OtpMethod::Email;
    }
}
