<?php

// Public runtime configuration handed to the browser as window.__APP_CONFIG__
// (see resources/views/app.blade.php). This is an explicit allowlist: every value
// here ends up in the DOM, so only add values that are safe to expose.
//
// The point of this file is that the JS bundle stays free of environment-specific
// values, so it can be built once and deployed to any customer installation.
// Never reintroduce VITE_* variables for these — vite.config.js blocks them.
return [
    // Mirrors the reverb connection in config/broadcasting.php. Deliberately no
    // defaults: unset values arrive as null and the fallbacks in
    // resources/js/bootstrap.js apply, which keeps the previous behaviour.
    // REVERB_APP_SECRET and REVERB_APP_ID must never be listed here.
    'reverb' => [
        'key' => env('REVERB_APP_KEY'),
        'cluster' => env('REVERB_APP_CLUSTER'),
        'host' => env('REVERB_HOST'),
        'port' => env('REVERB_PORT'),
    ],
];
