<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class Localization
{
    public function handle(Request $request, Closure $next)
    {
        if (Session::has('locale')) {
            App::setLocale(Session::get('locale'));
        } else {
            $userLocale = \Auth::guest() ? 'en' : \Auth::user()->language;
            Session::put('locale', $userLocale);
            App::setLocale($userLocale);
        }
        return $next($request);
    }
}
