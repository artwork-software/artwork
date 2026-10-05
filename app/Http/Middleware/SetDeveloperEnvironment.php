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
     * In Produktion wirkungslos: ein DB-Flag darf dort keine Stacktraces freischalten.
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
