<?php

/*
|--------------------------------------------------------------------------
| Spectator (OpenAPI contract tests)
|--------------------------------------------------------------------------
|
| Validates requests and responses of the app API against
| artwork/Modules/AppApi/openapi.yaml in the feature tests (AppContractTest).
| The validation runs as a middleware, so every group that serves a
| contract-tested route has to be listed here.
|
*/

return [
    'default' => 'local',

    'sources' => [
        'local' => [
            'source' => 'local',
            'base_path' => base_path(),
        ],
    ],

    'path_prefix' => '',

    'error_format' => env('SPECTATOR_ERROR_FORMAT', 'text'),

    'middleware_groups' => ['api', 'api.app', 'api.machine'],
];
