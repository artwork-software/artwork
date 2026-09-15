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

    /*
    |--------------------------------------------------------------------------
    | Entwicklungs-Stub
    |--------------------------------------------------------------------------
    |
    | Der Stub (`POST /ai/dev-stub/chat`) streamt feste Event-Skripte nach Protokoll 1, damit die
    | KI-Bar ohne artwork-ai gebaut und getestet werden kann. Die Routen registriert der
    | AiAssistantServiceProvider ausschliesslich in der Umgebung `local`.
    |
    | `schema_path` zeigt auf die JSON-Schemata der SSE-Events aus dem Spec-Workspace. Das
    | Verzeichnis gehoert nicht zu diesem Repository (`specs/` ist lokal ein Symlink); fehlt es,
    | validiert der Stub nicht und der zugehoerige Test wird uebersprungen.
    |
    */

    'dev_stub' => [
        'schema_path' => env('AI_DEV_STUB_SCHEMA_PATH', base_path('specs/contracts/sse-events')),
    ],
];
