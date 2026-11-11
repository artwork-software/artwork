<?php

/*
|--------------------------------------------------------------------------
| OpenAPI-Erzeugung (dedoc/scramble)
|--------------------------------------------------------------------------
*/

return [

    // Neben der Maschinen-API wird POST /ai/session mitdokumentiert: Die Route liegt bewusst unter
    // dem Web-Guard (Konzept 0.4 §3), artwork-ai muss aber wissen, woher das Session-Token kommt.
    // Mehrere Includes bedeuten in scramble: Server ist "/" und die Pfade bleiben vollstaendig —
    // genau das ist hier gewollt, sonst waeren /ai/session und /api/v1/ai/session nicht zu
    // unterscheiden.
    'api_path' => [
        'include' => ['api/v1', 'ai/session', 'ai/conversations'],
    ],

    'export_path' => 'openapi.json',

    // Ohne diese Erweiterung exportiert scramble spatie/laravel-data-Antworten als leeres Objekt
    // (die Auswertung von laravel-data gehoert zur Pro-Version) — der Contract waere an genau den
    // Stellen wertlos, an denen artwork-ai ihn liest (0.4 §5, core-api-context-projects).
    'extensions' => [
        \Artwork\Core\Api\Scramble\LaravelDataToSchema::class,
    ],

    'security_strategy' => \Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy::class,

    'info' => [
        'version' => '1.0.0',
        'description' => 'Machine API of artwork. Authenticate with a bearer token created under '
            . 'Tool settings → Interfaces. Every endpoint requires the scope noted on it.',
    ],

    'ui' => [
        'title' => 'artwork Machine API',
    ],

];
