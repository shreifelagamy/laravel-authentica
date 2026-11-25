<?php

use Shreifelagamy\Authentica\Support\Mask;

it('keeps only the start and the last four digits of a number', function () {
    expect(Mask::phone('+966551234567'))->toBe('+966*****4567')
        ->and(Mask::phone('0551234567'))->toBe('055***4567')
        ->and(Mask::phone('12345'))->toBe('*****');
});

it('masks the numbers inside a text', function () {
    expect(Mask::phonesIn('The phone +966551234567 is blocked, template 31'))
        ->toBe('The phone +966*****4567 is blocked, template 31');
});
