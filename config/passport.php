<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passport Guard
    |--------------------------------------------------------------------------
    |
    | Here you may specify which authentication guard Passport will use when
    | authenticating users. This value should correspond with one of your
    | guards that is already present in your "auth" configuration file.
    |
    */

    'guard' => 'web',

    'middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    |
    | Passport uses encryption keys while generating secure access tokens for
    | your application. By default, the keys are stored as local files but
    | can be set via environment variables when that is more convenient.
    |
    */

    'private_key' => env('PASSPORT_PRIVATE_KEY'),

    'public_key' => env('PASSPORT_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Passport Database Connection
    |--------------------------------------------------------------------------
    |
    | By default, Passport's models will utilize your application's default
    | database connection. If you wish to use a different connection you
    | may specify the configured name of the database connection here.
    |
    */

    'connection' => env('PASSPORT_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Extended Tokens (bewusste Ausnahme)
    |--------------------------------------------------------------------------
    |
    | Einzelne Tokens (per jti) werden über ihren signierten exp-Claim hinaus bis zur
    | angegebenen Deadline akzeptiert. Nur für Integrationen, deren Gegenseite Tokens nicht
    | rotieren kann — zeitlich begrenzt halten und die Deadline als Termin einplanen.
    | Signatur- und Revoke-Prüfung bleiben aktiv (Kill-Switch: oauth_access_tokens.revoked = 1).
    | Solange ein Eintrag aktiv ist, darf das Passport-Keypair nicht rotiert werden.
    |
    | Format: JSON-Objekt {"<jti>": "YYYY-MM-DD HH:MM:SS"}. Standard: leer = inaktiv.
    | Ungültiges JSON oder kein Objekt ergibt ebenfalls eine leere Liste.
    |
    */

    'extended_tokens' => is_array($extendedTokens = json_decode((string) env('PASSPORT_EXTENDED_TOKENS', '{}'), true))
        ? $extendedTokens
        : [],

];
