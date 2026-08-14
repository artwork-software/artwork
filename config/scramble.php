<?php

/*
|--------------------------------------------------------------------------
| OpenAPI-Erzeugung (dedoc/scramble)
|--------------------------------------------------------------------------
*/

return [

    'api_path' => 'api/v1',

    'export_path' => 'openapi.json',

    'info' => [
        'version' => '1.0.0',
        'description' => 'Machine API of artwork. Authenticate with a bearer token created under '
            . 'Tool settings → Interfaces. Every endpoint requires the scope noted on it.',
    ],

    'ui' => [
        'title' => 'artwork Machine API',
    ],

];
