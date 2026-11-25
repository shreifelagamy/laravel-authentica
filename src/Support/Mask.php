<?php

namespace Shreifelagamy\Authentica\Support;

/**
 * Hides most digits of phone numbers, so they don't end up in logs and error trackers in full.
 */
final class Mask
{
    /**
     * "+966551234567" becomes "+966*****4567".
     */
    public static function phone(string $phone): string
    {
        $length = mb_strlen($phone);

        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        $start = str_starts_with($phone, '+') ? 4 : 3;

        return mb_substr($phone, 0, $start).str_repeat('*', $length - $start - 4).mb_substr($phone, -4);
    }

    /**
     * Masks every run of 7 or more digits (with an optional leading +) in a text, such as an API error message.
     */
    public static function phonesIn(string $text): string
    {
        return (string) preg_replace_callback(
            '/\+?\d{7,}/',
            fn (array $match): string => self::phone($match[0]),
            $text,
        );
    }
}
