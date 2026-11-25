<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API key
    |--------------------------------------------------------------------------
    |
    | Created in the Authentica portal (https://portal.authentica.sa). It is
    | sent in the X-Authorization header of every request.
    |
    */

    'api_key' => env('AUTHENTICA_API_KEY'),

    'base_url' => env('AUTHENTICA_BASE_URL', 'https://api.authentica.sa/api/v2'),

    /*
    | Seconds to wait for Authentica before giving up. Requests are never
    | retried automatically: every resend can deliver another message and
    | spend credits.
    */

    'timeout' => (int) env('AUTHENTICA_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | One-time codes
    |--------------------------------------------------------------------------
    |
    | The default method for one-time codes: "sms", "whatsapp" or "email".
    | Leave the template empty to use the default template of the method in
    | the Authentica dashboard.
    |
    */

    'otp' => [
        'method' => env('AUTHENTICA_OTP_METHOD', 'sms'),
        'template_id' => env('AUTHENTICA_OTP_TEMPLATE_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS sender name
    |--------------------------------------------------------------------------
    |
    | The approved sender name for custom SMS messages. Leave it empty to use
    | the sender name set in the Authentica dashboard.
    |
    */

    'sender_name' => env('AUTHENTICA_SENDER_NAME'),

    /*
    |--------------------------------------------------------------------------
    | Countries
    |--------------------------------------------------------------------------
    |
    | ISO 3166-1 alpha-2 codes of the countries whose mobile numbers may be
    | messaged. Local numbers ("0551234567") are read as numbers of these
    | countries, in this order. Anything else is rejected before a request is
    | made, so no credit is spent on it.
    |
    */

    'countries' => ['SA'],

    /*
    |--------------------------------------------------------------------------
    | Notification channel name
    |--------------------------------------------------------------------------
    |
    | The name notifications return from via(). The channel is always
    | available as "authentica" and as AuthenticaChannel::class; set this to
    | "sms" (for example) to add a provider-neutral name as well.
    |
    */

    'channel_name' => env('AUTHENTICA_CHANNEL_NAME', 'authentica'),

];
