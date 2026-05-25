<?php

namespace Artwork\Modules\ExternalAccess\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class SwapExternalSessionConfig
{
    public function handle(Request $request, Closure $next): Response
    {
        Config::set('session.cookie', config('external_access.session.cookie'));
        Config::set('session.lifetime', config('external_access.session.lifetime'));
        Config::set('session.expire_on_close', config('external_access.session.expire_on_close'));

        return $next($request);
    }
}
