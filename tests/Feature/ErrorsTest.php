<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Shreifelagamy\Authentica\Exceptions\AuthenticaException;
use Shreifelagamy\Authentica\Exceptions\AuthenticaInvalidApiKeyException;
use Shreifelagamy\Authentica\Exceptions\AuthenticaRateLimitedException;
use Shreifelagamy\Authentica\Exceptions\AuthenticaRequestRejectedException;
use Shreifelagamy\Authentica\Exceptions\AuthenticaUnavailableException;
use Shreifelagamy\Authentica\Facades\Authentica;

it('turns error answers into typed exceptions', function (int $status, string $exception) {
    fakeAuthentica([API.'/send-sms' => Http::response(['errors' => [['message' => 'Something went wrong']]], $status)]);

    expect(fn () => Authentica::sendSms('0551234567', 'Hello'))
        ->toThrow($exception, 'Something went wrong');
})->with([
    'unauthorised' => [401, AuthenticaInvalidApiKeyException::class],
    'rejected' => [422, AuthenticaRequestRejectedException::class],
    'rate limited' => [429, AuthenticaRateLimitedException::class],
    'unavailable' => [503, AuthenticaUnavailableException::class],
    'server error' => [500, AuthenticaUnavailableException::class],
    'anything else' => [404, AuthenticaException::class],
]);

it('keeps every exception under the base class', function () {
    fakeAuthentica([API.'/balance' => Http::response(['errors' => [['message' => 'Unauthorized']]], 401)]);

    expect(fn () => Authentica::balance())->toThrow(AuthenticaException::class);
});

it('says how long to wait when rate limited', function () {
    fakeAuthentica([API.'/send-otp' => Http::response(['errors' => [['message' => 'Too many']]], 429, ['Retry-After' => '30'])]);

    try {
        Authentica::sendOtp('0551234567', '123456');
    } catch (AuthenticaRateLimitedException $exception) {
        expect($exception->retryAfter())->toBe(30)->and($exception->getCode())->toBe(429);

        return;
    }

    $this->fail('No exception was thrown.');
});

it('masks phone numbers Authentica puts in error messages', function () {
    fakeAuthentica([API.'/send-sms' => Http::response(['errors' => [['message' => 'The number +966551234567 is blocked']]], 422)]);

    expect(fn () => Authentica::sendSms('0551234567', 'Hello'))
        ->toThrow(AuthenticaRequestRejectedException::class, '+966*****4567');
});

it('reports a connection failure as unavailable', function () {
    Http::fake(fn () => throw new ConnectionException('timed out'));

    expect(fn () => Authentica::balance())->toThrow(AuthenticaUnavailableException::class, 'timed out');
});

it('never retries a failed send', function () {
    fakeAuthentica([API.'/send-otp' => Http::response(['errors' => [['message' => 'Down']]], 503)]);

    rescue(fn () => Authentica::sendOtp('0551234567', '123456'), report: false);

    Http::assertSentCount(1);
});
