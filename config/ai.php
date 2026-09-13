<?php

return [
    /*
    |--------------------------------------------------------------------------
    | KI-Assistent — Instanz-Konfiguration
    |--------------------------------------------------------------------------
    |
    | Diese Werte setzt der Betreiber beim Deploy, nicht das Haus. `enabled` ist der
    | globale Kill-Switch: steht er auf false, ist der Assistent unabhängig vom
    | Haus-Setting aus.
    |
    | `endpoint_url` ist bewusst kein Haus-Setting. Ein editierbarer Endpunkt hiesse,
    | dass jeder mit Einstellungsrechten die Instanz auf einen fremden Server zeigen
    | lassen kann, an den dann Session-Tokens gehen.
    |
    */

    'enabled' => (bool) env('AI_ENABLED', false),

    'endpoint_url' => env('AI_ENDPOINT_URL'),
];
