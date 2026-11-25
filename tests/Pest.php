<?php

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Shreifelagamy\Authentica\Tests\TestCase;

uses(TestCase::class)->in('Unit', 'Feature');

const API = 'https://api.authentica.sa/api/v2';

/**
 * Fakes every Authentica endpoint with its documented success answer.
 */
function fakeAuthentica(array $overrides = []): void
{
    Http::swap(new Factory(app('events')));
    Http::preventStrayRequests();

    Http::fake([
        API.'/send-sms' => Http::response(['success' => true, 'data' => null, 'message' => 'SMS sent']),
        API.'/send-otp' => Http::response(['success' => true, 'data' => null, 'message' => 'OTP send successfully']),
        API.'/verify-otp' => Http::response(['status' => true, 'message' => 'OTP verified successfully']),
        API.'/balance' => Http::response(['success' => true, 'data' => ['balance' => 21934], 'message' => 'Balance retrieved successfully']),
        ...$overrides,
    ]);
}
