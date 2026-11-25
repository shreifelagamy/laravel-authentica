<?php

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\AssertionFailedError;
use Shreifelagamy\Authentica\Exceptions\AuthenticaInvalidPhoneException;
use Shreifelagamy\Authentica\Facades\Authentica;
use Shreifelagamy\Authentica\Testing\SentMessage;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->authentica = Authentica::fake();
});

it('records messages instead of sending them', function () {
    Authentica::sendSms('0551234567', 'Hello');
    Authentica::sendOtp('0551234567', '482913');

    $this->authentica->assertSmsSent(fn (SentMessage $sms) => $sms->content === 'Hello');
    $this->authentica->assertOtpSent(fn (SentMessage $otp) => $otp->code === '482913');
    $this->authentica->assertSentCount(2);
    Http::assertNothingSent();
});

it('checks numbers like the real client', function () {
    Authentica::sendSms('12345', 'Hello');
})->throws(AuthenticaInvalidPhoneException::class);

it('verifies the last code sent to a number', function () {
    Authentica::sendOtp('0551234567', '482913');

    expect(Authentica::verifyOtp('+966551234567', '482913'))->toBeTrue()
        ->and(Authentica::verifyOtp('0551234567', '111111'))->toBeFalse()
        ->and(Authentica::verifyOtp('0559998888', '482913'))->toBeFalse();
});

it('can be told how to verify', function () {
    $this->authentica->verifyUsing(fn (string $phone, string $code) => $code === '999999');

    expect(Authentica::verifyOtp('0551234567', '999999'))->toBeTrue();
});

it('has a balance that can be set', function () {
    expect(Authentica::balance())->toBe(100);

    $this->authentica->setBalance(3);

    expect(Authentica::balance())->toBe(3);
});

it('fails the assertions when nothing matches', function () {
    Authentica::sendSms('0551234567', 'Hello');

    expect(fn () => $this->authentica->assertOtpSent())->toThrow(AssertionFailedError::class)
        ->and(fn () => $this->authentica->assertSmsNotSent())->toThrow(AssertionFailedError::class)
        ->and(fn () => $this->authentica->assertNothingSent())->toThrow(AssertionFailedError::class)
        ->and(fn () => $this->authentica->assertSmsSent(times: 2))->toThrow(AssertionFailedError::class);
});

it('passes assertNothingSent when nothing was sent', function () {
    $this->authentica->assertNothingSent();
    $this->authentica->assertSmsNotSent();
    $this->authentica->assertOtpNotSent();
});
