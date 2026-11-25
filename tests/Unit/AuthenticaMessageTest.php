<?php

use Shreifelagamy\Authentica\Enums\OtpMethod;
use Shreifelagamy\Authentica\Notifications\AuthenticaMessage;

it('builds an SMS', function () {
    $message = AuthenticaMessage::sms('Hello')->sender('YoungFit')->to('0551234567');

    expect($message->isOtp())->toBeFalse()
        ->and($message->content)->toBe('Hello')
        ->and($message->sender)->toBe('YoungFit')
        ->and($message->to)->toBe('0551234567');
});

it('builds a one-time code', function () {
    $message = AuthenticaMessage::otp('123456')->via(OtpMethod::Whatsapp)->template(31)->fallbackPhone('0551234567');

    expect($message->isOtp())->toBeTrue()
        ->and($message->code)->toBe('123456')
        ->and($message->method)->toBe(OtpMethod::Whatsapp)
        ->and($message->template)->toBe(31)
        ->and($message->fallbackPhone)->toBe('0551234567');
});
