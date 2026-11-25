<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Shreifelagamy\Authentica\Enums\OtpMethod;
use Shreifelagamy\Authentica\Exceptions\AuthenticaException;
use Shreifelagamy\Authentica\Exceptions\AuthenticaInvalidPhoneException;
use Shreifelagamy\Authentica\Facades\Authentica;

beforeEach(fn () => fakeAuthentica());

it('sends an SMS with the key, the international number and the sender name', function () {
    config(['authentica.sender_name' => 'YoungFit']);

    $response = Authentica::sendSms('0551234567', 'مرحبا بكم في يونغ فت');

    expect($response->successful())->toBeTrue();

    Http::assertSent(fn (Request $request) => $request->url() === API.'/send-sms'
        && $request->method() === 'POST'
        && $request->header('X-Authorization') === ['test-key']
        && $request->header('Accept') === ['application/json']
        && $request->data() === ['phone' => '+966551234567', 'message' => 'مرحبا بكم في يونغ فت', 'sender_name' => 'YoungFit']);
});

it('leaves the sender name out when none is set', function () {
    Authentica::sendSms('0551234567', 'Hello');

    Http::assertSent(fn (Request $request) => ! array_key_exists('sender_name', $request->data()));
});

it('uses the sender name passed in over the configured one', function () {
    config(['authentica.sender_name' => 'YoungFit']);

    Authentica::sendSms('0551234567', 'Hello', 'Elevxi');

    Http::assertSent(fn (Request $request) => $request->data()['sender_name'] === 'Elevxi');
});

it('sends our own one-time code by SMS by default', function () {
    Authentica::sendOtp('0551234567', '482913');

    Http::assertSent(fn (Request $request) => $request->url() === API.'/send-otp'
        && $request->data() === ['method' => 'sms', 'phone' => '+966551234567', 'otp' => '482913']);
});

it('sends a code by WhatsApp with a template and an SMS fallback', function () {
    Authentica::sendOtp('0551234567', method: OtpMethod::Whatsapp, template: 31, fallbackPhone: '0551234567');

    Http::assertSent(fn (Request $request) => $request->data() === [
        'method' => 'whatsapp',
        'phone' => '+966551234567',
        'template_id' => 31,
        'fallback_phone' => '+966551234567',
    ]);
});

it('uses the configured method and template', function () {
    config(['authentica.otp.method' => 'whatsapp', 'authentica.otp.template_id' => 7]);

    Authentica::sendOtp('0551234567');

    Http::assertSent(fn (Request $request) => $request->data()['method'] === 'whatsapp'
        && $request->data()['template_id'] === 7);
});

it('sends a code by email to an email address', function () {
    Authentica::sendOtp('parent@example.com', method: 'email');

    Http::assertSent(fn (Request $request) => $request->data() === ['method' => 'email', 'email' => 'parent@example.com']);
});

it('rejects a bad number before any request', function () {
    Authentica::sendSms('12345', 'Hello');
})->throws(AuthenticaInvalidPhoneException::class);

it('never sends a request for a bad number', function () {
    rescue(fn () => Authentica::sendOtp('+971501234567', '123456'), report: false);

    Http::assertNothingSent();
});

it('rejects a code that is not only digits', function () {
    Authentica::sendOtp('0551234567', '12ab56');
})->throws(InvalidArgumentException::class);

it('rejects an unknown method', function () {
    Authentica::sendOtp('0551234567', method: 'pigeon');
})->throws(InvalidArgumentException::class);

it('verifies a code', function () {
    expect(Authentica::verifyOtp('0551234567', '482913'))->toBeTrue();

    Http::assertSent(fn (Request $request) => $request->url() === API.'/verify-otp'
        && $request->data() === ['otp' => '482913', 'phone' => '+966551234567']);
});

it('verifies a code sent by email', function () {
    Authentica::verifyOtp('parent@example.com', '482913');

    Http::assertSent(fn (Request $request) => $request->data() === ['otp' => '482913', 'email' => 'parent@example.com']);
});

it('answers false for a wrong or expired code', function (array $answer, int $status) {
    fakeAuthentica([API.'/verify-otp' => Http::response($answer, $status)]);

    expect(Authentica::verifyOtp('0551234567', '000000'))->toBeFalse();
})->with([
    'status false' => [['status' => false, 'message' => 'Invalid OTP'], 200],
    'HTTP 422' => [['errors' => [['message' => 'Invalid OTP']]], 422],
]);

it('reads the balance', function () {
    expect(Authentica::balance())->toBe(21934);

    Http::assertSent(fn (Request $request) => $request->url() === API.'/balance' && $request->method() === 'GET');
});

it('uses the configured base URL', function () {
    config(['authentica.base_url' => 'https://example.test/api/v2/']);
    Http::fake(['https://example.test/api/v2/balance' => Http::response(['success' => true, 'data' => ['balance' => 5]])]);

    expect(Authentica::balance())->toBe(5);
});

it('needs an API key', function () {
    config(['authentica.api_key' => null]);

    Authentica::balance();
})->throws(AuthenticaException::class, 'AUTHENTICA_API_KEY');
