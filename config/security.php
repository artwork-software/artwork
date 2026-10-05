<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy – Rollout-Schalter
    |--------------------------------------------------------------------------
    |
    | Ausgewertet von Artwork\Core\Http\Middleware\SecurityHeaders. Solange der
    | Schalter aus ist, wird die Policy nur als Content-Security-Policy-Report-Only
    | gesendet (Browser meldet Verstoesse in der Konsole, blockiert nichts). Erst
    | nach einem Beobachtungszeitraum ohne Verstoesse SECURITY_CSP_ENFORCE=true
    | setzen. Die uebrigen Header (nosniff, Referrer-Policy, X-Frame-Options,
    | Permissions-Policy, HSTS bei https) sind immer scharf.
    |
    */

    'csp_enforce' => (bool) env('SECURITY_CSP_ENFORCE', false),

];
