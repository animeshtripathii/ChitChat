<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS Driver
    |--------------------------------------------------------------------------
    |
    | Supported values: "local", "sandbox"
    |
    | "local": Generates random OTP codes and writes them directly to storage/logs/laravel.log.
    | "sandbox": Enables a mock gateway for whitelisted numbers using the "123456" placeholder.
    |
    */
    'driver' => env('SMS_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Sandbox / Whitelist Configuration
    |--------------------------------------------------------------------------
    |
    | List of whitelisted phone numbers that skip real network SMS dispatches.
    | They automatically accept the fixed placeholder OTP code.
    |
    */
    'sandbox' => [
        'whitelist' => explode(',', env('SMS_SANDBOX_WHITELIST', '1234567890,1111111111,2222222222')),
        'placeholder_otp' => '123456',
    ],

    /*
    |--------------------------------------------------------------------------
    | Twilio Free-Tier Credentials
    |--------------------------------------------------------------------------
    |
    | Optional credentials used by the sandbox driver to dispatch real SMS
    | codes to external beta testers whose numbers are not whitelisted.
    |
    */
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],
];
