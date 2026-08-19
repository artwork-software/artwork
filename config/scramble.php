<?php

/*
|--------------------------------------------------------------------------
| OpenAPI-Erzeugung (dedoc/scramble)
|--------------------------------------------------------------------------
*/

return [

    'api_path' => 'api/v1',

    'export_path' => 'openapi.json',

    // Fest, sonst stünde hier die APP_URL des Rechners, der die Spezifikation erzeugt hat.
    'servers' => [
        'Production' => 'https://your-artwork-domain.example/api/v1',
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
