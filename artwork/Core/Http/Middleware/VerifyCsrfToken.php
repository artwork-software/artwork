<?php

namespace Artwork\Core\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Bewusst leer: Inertia/axios senden X-XSRF-TOKEN (Cookie) automatisch, die
     * fetch()-Aufrufe (Chat, Keypair) und bootstrap-external.js senden X-CSRF-TOKEN aus
     * dem Meta-Tag, Blade-Formulare (OAuth-Authorize) nutzen @csrf. Eingehende Webhooks
     * gibt es nicht. Ausnahmen hier nur mit konkretem Pfad und Begruendung eintragen —
     * nie wieder '*' (Sicherheits-Audit 21.09.2026, Befund D).
     */
    protected $except = [];
}
