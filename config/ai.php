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
    | Lese-Endpunkte fuer artwork-ai (B1)
    |--------------------------------------------------------------------------
    |
    | `text_limit`: Freitextfelder (name, description, notes) werden in den DTOs gekuerzt —
    | in artwork, nicht in artwork-ai, damit die Grenze fuer jeden Konsumenten gilt (B4 §4).
    | `list_limit`: harte Obergrenze je Liste; darueber wird `truncated` gesetzt (B1 §4).
    | `max_range_days`: laengster erlaubter Abfragezeitraum (B1 §4).
    |
    */

    'text_limit' => (int) env('AI_TEXT_LIMIT', 200),

    'list_limit' => 50,

    'max_range_days' => 180,

    /*
     * Tagesfenster fuer Raumbelegung und freie Slots (B1 §5). Ausserhalb wird weder Belegung
     * gezaehlt noch ein freier Slot angeboten — ein Raum ist um 3 Uhr morgens zwar frei, aber
     * kein sinnvoller Vorschlag.
     */
    'day_window' => [
        'from' => env('AI_DAY_WINDOW_FROM', '08:00'),
        'to' => env('AI_DAY_WINDOW_TO', '22:00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bestaetigungspflichtige Aktionen (W2)
    |--------------------------------------------------------------------------
    |
    | Laengste Bestaetigungsfrist einer Aktion (Konzept W2 §1: 10 Minuten). Der Turn-Endpunkt weist
    | eine laengere Frist ab, der Ablauf-Job raeumt offene Aktionen danach weg.
    |
    */

    'action_ttl_minutes' => (int) env('AI_ACTION_TTL_MINUTES', 10),

    /*
     * Fenster fuer „Rueckgaengig" nach einer ausgefuehrten Aktion (Konzept W3 §2: 24 Stunden).
     */
    'undo_hours' => (int) env('AI_UNDO_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Wiki
    |--------------------------------------------------------------------------
    |
    | Ziel der Weiterleitung `GET /help/{pfad}` (core-help-redirect). Die Bar macht nur relative
    | Ziele zu Links (C4 §4); ueber diese Basis kommen Antworten aus der Dokumentation trotzdem
    | ins Wiki. Das Sprachsegment `/de/` wird nach der Sprache des Benutzers gesetzt.
    |
    */

    'wiki_base_url' => env('AI_WIKI_BASE_URL', 'https://wiki.artwork.software/de/docs'),

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
