<?php

use Shreifelagamy\Authentica\Exceptions\AuthenticaInvalidPhoneException;
use Shreifelagamy\Authentica\Support\PhoneNumberNormalizer;

it('turns the ways people write a Saudi mobile into the international format', function (string $input) {
    expect((new PhoneNumberNormalizer(['SA']))->normalize($input))->toBe('+966551234567');
})->with([
    'local' => '0551234567',
    'without the zero' => '551234567',
    'with spaces' => '055 123 4567',
    'with dashes' => '055-123-4567',
    'international' => '+966551234567',
    'international with spaces' => '+966 55 123 4567',
    'country code without plus' => '966551234567',
    'double zero' => '00966551234567',
    'Arabic digits' => '٠٥٥١٢٣٤٥٦٧',
    'Persian digits' => '۰۵۵۱۲۳۴۵۶۷',
    'with direction marks' => "\u{200E}+966551234567",
]);

it('rejects what is not a Saudi mobile', function (string $input) {
    (new PhoneNumberNormalizer(['SA']))->normalize($input);
})->with([
    'empty' => '',
    'letters' => 'not a number',
    'too short' => '055123',
    'too long' => '05512345678',
    'landline' => '0112345678',
    'another country' => '+971501234567',
])->throws(AuthenticaInvalidPhoneException::class);

it('allows other countries when they are configured', function () {
    $normalizer = new PhoneNumberNormalizer(['SA', 'AE']);

    expect($normalizer->normalize('+971501234567'))->toBe('+971501234567')
        ->and($normalizer->normalize('0551234567'))->toBe('+966551234567')
        ->and($normalizer->isValid('+201001234567'))->toBeFalse();
});

it('masks the number in the error message', function () {
    try {
        (new PhoneNumberNormalizer(['SA']))->normalize('+971501234567');
    } catch (AuthenticaInvalidPhoneException $exception) {
        expect($exception->getMessage())->not->toContain('501234567')->toContain('+971');

        return;
    }

    $this->fail('No exception was thrown.');
});
