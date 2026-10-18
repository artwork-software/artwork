<?php

// Server-seitiges Senden an Reverb: optional direkt an den Reverb-Container (z. B. artwork_reverb:8080, http)
// statt über die öffentliche Domain. REVERB_HOST/PORT/SCHEME bleiben für den Browser (config/frontend.php)
// zuständig; ohne REVERB_INTERNAL_HOST geht der Server wie bisher über diese öffentliche Adresse. Der
// Umweg kostete je Broadcast eine DNS-Auflösung der eigenen Domain – hing der Resolver, scheiterte er.
$reverbInternalHost = env('REVERB_INTERNAL_HOST') ?: null;
$reverbSendScheme = $reverbInternalHost !== null
    ? (env('REVERB_INTERNAL_SCHEME') ?: 'http')
    : env('REVERB_SCHEME', 'https');

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "reverb", "redis", "log", "null"
    |
    | BROADCAST_CONNECTION is the name Laravel uses since version 11. Installations
    | that still carry the older BROADCAST_DRIVER in their .env keep working through
    | the fallback below.
    |
    */

    'default' => env('BROADCAST_CONNECTION', env('BROADCAST_DRIVER', 'null')),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [
        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => $reverbInternalHost ?? env('REVERB_HOST'),
                'port' => $reverbInternalHost !== null
                    ? (env('REVERB_INTERNAL_PORT') ?: 8080)
                    : env('REVERB_PORT', 443),
                'scheme' => $reverbSendScheme,
                'useTLS' => $reverbSendScheme === 'https',
                // Pusher-Client setzt diesen Wert je Anfrage als Guzzle-"timeout" (Standard 30 s). Live-Hinweise
                // gehen synchron raus (ShouldBroadcastNow) – ein hängender Reverb blockierte sonst den Request
                // bis zu 30 s je Empfänger*in. Fehlschläge fängt NotificationService ab (BroadcastException).
                'timeout' => (float) (env('REVERB_HTTP_TIMEOUT') ?: 3),
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
                // Laravel-Standard 10 s für den Verbindungsaufbau, den der Pusher-Client nicht überschreibt
                'connect_timeout' => (float) (env('REVERB_HTTP_CONNECT_TIMEOUT') ?: 2),
                'timeout' => (float) (env('REVERB_HTTP_TIMEOUT') ?: 3),
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
