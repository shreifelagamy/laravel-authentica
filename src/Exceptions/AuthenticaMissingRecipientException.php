<?php

namespace Shreifelagamy\Authentica\Exceptions;

/**
 * A notification could not be sent because the notifiable has no phone number (or email address for email codes).
 */
class AuthenticaMissingRecipientException extends AuthenticaException
{
    public static function for(object $notifiable, string $notification): self
    {
        return new self(sprintf(
            'Could not send %s: %s has no recipient for Authentica. Add a routeNotificationForAuthentica() method or a "phone" attribute.',
            $notification,
            $notifiable::class,
        ));
    }
}
