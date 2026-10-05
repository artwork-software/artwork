<?php

namespace Artwork\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Carbon\CarbonPeriod;

class Localization
{
    public function handle(Request $request, Closure $next)
    {
        $supported = ['de', 'en']; // ggf. erweitern

        // Reihenfolge: 1) User, 2) Session, 3) Cookie, 4) Browser Accept-Language oder App-Default
        if (Auth::check() && filled(Auth::user()->language)) {
            $locale = Auth::user()->language;
        } elseif (Session::has('locale')) {
            $locale = Session::get('locale');
        } elseif ($cookie = $request->cookie('locale')) {
            $locale = $cookie;
        } else {
            $locale = $request->getPreferredLanguage($supported) ?? config('app.locale');
        }

        if (!in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);
        CarbonPeriod::setLocale($locale);
        Session::put('locale', $locale);
        Cookie::queue(cookie()->forever('locale', $locale));

        return $next($request);
    }
}
