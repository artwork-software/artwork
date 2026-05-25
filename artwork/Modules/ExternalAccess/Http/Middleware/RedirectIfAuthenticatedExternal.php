<?php

namespace Artwork\Modules\ExternalAccess\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticatedExternal
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('external')->check()) {
            return redirect()->route('external.dashboard');
        }

        return $next($request);
    }
}
