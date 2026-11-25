<?php

namespace Shreifelagamy\Authentica\Exceptions;

use Shreifelagamy\Authentica\Support\Mask;

/**
 * The number is not a valid mobile number of an allowed country. Thrown before any request, so no credit is spent.
 */
class AuthenticaInvalidPhoneException extends AuthenticaException
{
    /**
     * @param  array<int, string>  $countries
     */
    public static function make(string $phone, array $countries): self
    {
        return new self(sprintf(
            'The number "%s" is not a valid mobile number for %s.',
            Mask::phone($phone),
            implode(', ', $countries),
        ));
    }
}
