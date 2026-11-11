<?php

namespace Artwork\Modules\AiAssistant\Support;

use Illuminate\Support\Facades\Config;

/**
 * Rechtepruefungen der Oberflaeche unter dem API-Guard ausfuehren (core-write-endpoints-dry-run).
 *
 * `spatie/laravel-permission` leitet den Guard einer Pruefung aus `config('auth.defaults.guard')`
 * ab (`Guard::getDefaultName()`), und `auth:api` setzt diesen Default zur Laufzeit auf `api`
 * (`AuthManager::shouldUse()`). Weil der Rechtekatalog und die Rollen ausschliesslich mit
 * `guard_name = web` existieren, liefern `can()` und `hasRole()` im API-Request sonst fuer jedes
 * Hausrecht und fuer die Admin-Rolle still `false` — die Policies der Oberflaeche waeren an den
 * Schreib-Endpunkten wertlos, und der Admin-Durchgriff aus `Gate::before` fiele weg.
 *
 * Der Helfer setzt den Default fuer die Dauer des Rueckrufs auf `web`. Damit faellt an den
 * Schreib-Endpunkten exakt dieselbe Entscheidung wie in der Oberflaeche.
 */
final class WebGuardScope
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function run(callable $callback): mixed
    {
        $previous = Config::get('auth.defaults.guard');
        Config::set('auth.defaults.guard', 'web');

        try {
            return $callback();
        } finally {
            Config::set('auth.defaults.guard', $previous);
        }
    }
}
