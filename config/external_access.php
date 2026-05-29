<?php

return [
    'session' => [
        'cookie' => env('EXTERNAL_SESSION_COOKIE', 'artwork_external_session'),
        'lifetime' => (int) env('EXTERNAL_SESSION_LIFETIME_MINUTES', 120),
        'expire_on_close' => true,
    ],

    'login_token' => [
        'lifetime_minutes' => 15,
    ],

    'absolute_session_lifetime_minutes' => 480,

    'rate_limits' => [
        'request_link_per_email_per_hour' => 3,
        'request_link_per_ip_per_hour' => 10,
        'redeem_token_per_ip_per_minute' => 10,
        'general_per_external_per_minute' => 30,
    ],
];
