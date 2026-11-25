<?php

namespace Shreifelagamy\Authentica\Support;

use Propaganistas\LaravelPhone\PhoneNumber;
use SensitiveParameter;
use Shreifelagamy\Authentica\Exceptions\AuthenticaInvalidPhoneException;

/**
 * Turns a mobile number as people type it ("055 123 4567", "9665…", "009665…", Arabic digits) into the international
 * format Authentica expects ("+966551234567"), and rejects anything that is not a mobile number of an allowed country.
 */
final class PhoneNumberNormalizer
{
    private const ARABIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    private const LATIN_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /**
     * @param  array<int, string>  $countries  ISO 3166-1 alpha-2 codes, in the order local numbers are tried
     */
    public function __construct(private readonly array $countries) {}

    /**
     * @throws AuthenticaInvalidPhoneException
     */
    public function normalize(#[SensitiveParameter] string $phone): string
    {
        $cleaned = $this->clean($phone);
        $number = new PhoneNumber($cleaned, $this->countries);

        if ($cleaned === '' || ! $number->isValid() || ! $number->isOfCountry($this->countries) || ! $number->isOfType('mobile')) {
            throw AuthenticaInvalidPhoneException::make($cleaned === '' ? $phone : $cleaned, $this->countries);
        }

        return $number->formatE164();
    }

    public function isValid(#[SensitiveParameter] string $phone): bool
    {
        try {
            $this->normalize($phone);

            return true;
        } catch (AuthenticaInvalidPhoneException) {
            return false;
        }
    }

    /**
     * @return array<int, string>
     */
    public function countries(): array
    {
        return $this->countries;
    }

    private function clean(#[SensitiveParameter] string $phone): string
    {
        $phone = str_replace(self::ARABIC_DIGITS, self::LATIN_DIGITS, trim($phone));
        $phone = (string) preg_replace('/[\s\-().\x{200E}\x{200F}]/u', '', $phone);

        return str_starts_with($phone, '00') ? '+'.substr($phone, 2) : $phone;
    }
}
