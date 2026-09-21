<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetDeveloperEnvironment
{
    /**
     * Handle an incoming request.
     *
     * Schaltet für Nutzer mit users.is_developer den Debug-Modus ein. In Produktion ist die
     * Middleware bewusst wirkungslos: ein DB-Flag darf dort keine Stacktraces/Request-Daten
     * auf Fehlerseiten freischalten (Sicherheits-Audit 21.09.2026, Abschnitt D).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction()) {
            return $next($request);
        }

        if (Auth::check() && Auth::user()->is_developer) {
            config(['app.debug' => true]);
            config(['app.env' => 'local']);
        }
        return $next($request);
    }
}
