<?php

/*
 * Ziggy-Routenkarte für das Frontend (Sicherheits-Audit 21.09.2026, G).
 *
 * - Ohne Gruppe (app.blade.php) erhält das interne Bundle alle benannten Routen
 *   abzüglich `except` (Tooling-/Auth-Server-Routen, die das Frontend nie per route() aufruft).
 * - Gruppe `external` (app-external.blade.php): nur die Routen, die die Seiten unter
 *   resources/js/Pages/ExternalAccess tatsächlich per route() nutzen. Externe Kontakte
 *   bekommen damit keine Karte der internen Admin-/Settings-Routen.
 *
 * Achtung: Bei einer Gruppe wird `except` von Ziggy NICHT zusätzlich angewendet,
 * die Gruppe ist eine reine Positivliste.
 */
return [
    'except' => [
        'telescope*',
        'horizon*',
        'debugbar*',
        'ignition*',
        'sanctum*',
        'passport*',
    ],

    'groups' => [
        'external' => [
            'external.*',
        ],
    ],
];
