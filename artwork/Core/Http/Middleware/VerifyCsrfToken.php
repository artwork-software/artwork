<?php

namespace Artwork\Core\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Leer: Inertia/axios senden X-XSRF-TOKEN, fetch()-Aufrufe und bootstrap-external.js X-CSRF-TOKEN
     * aus dem Meta-Tag, Blade-Formulare nutzen @csrf. Ausnahmen nur mit konkretem Pfad, nie '*'.
     */
    protected $except = [];
}
