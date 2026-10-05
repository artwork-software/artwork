<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    // Nur die eigene Origin (APP_URL) plus optionale Zusatz-Origins aus CORS_ALLOWED_ORIGINS
    // (kommagetrennt, z. B. ein separates Frontend). Kein '*': supports_credentials ist an,
    // ein reflektierter Origin wuerde jeder fremden Seite Requests mit Session-Cookie erlauben.
    'allowed_origins' => array_values(array_unique(array_filter(array_map('trim', array_merge(
        [env('APP_URL')],
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
    ))))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
