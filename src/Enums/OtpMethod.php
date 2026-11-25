<?php

namespace Shreifelagamy\Authentica\Enums;

/**
 * How Authentica delivers a one-time code.
 */
enum OtpMethod: string
{
    case Sms = 'sms';
    case Whatsapp = 'whatsapp';
    case Email = 'email';

    /**
     * Whether the code goes to a phone number (as opposed to an email address).
     */
    public function usesPhone(): bool
    {
        return $this !== self::Email;
    }
}
