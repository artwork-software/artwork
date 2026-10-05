<?php

// Nur technische Vorgaben. Alles Fachliche (Laufzeiten, Token-Lebensdauer, Session-Timeouts,
// Rate-Limits pro E-Mail/IP, Feature-Schalter) liegt in ExternalAccessSettings (Einstellungen → Externe Zugänge).
return [
    'session' => [
        'cookie' => env('EXTERNAL_SESSION_COOKIE', 'artwork_external_session'),
        'expire_on_close' => true,
    ],

    'rate_limits' => [
        'redeem_token_per_ip_per_minute' => 10,
        'general_per_external_per_minute' => 30,
    ],
];
