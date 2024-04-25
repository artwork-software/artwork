<?php

namespace Artwork\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Localization
{
    public function handle(Request $request, Closure $next)
    {
        // Prüfen, ob ein Benutzer angemeldet ist
        if (Auth::check()) {
            // Die Sprache des angemeldeten Benutzers anwenden
            $userLocale = Auth::user()->language;
            App::setLocale($userLocale);
            Session::put('locale', $userLocale);
        } else {
            // Benutzer ist nicht angemeldet; Standardsprache anwenden
            if (!Session::has('locale')) {
                App::setLocale('en');
                Session::put('locale', 'en');
            } else {
                // Anwenden der in der Session gespeicherten Sprache
                App::setLocale(Session::get('locale'));
            }
        }

        return $next($request);
    }
}
