<?php

namespace Artwork\Modules\ExternalAccess\Http\Middleware;

use Artwork\Modules\ExternalAccess\Services\ExternalAccessSettingsResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class SwapExternalSessionConfig
{
    public function __construct(
        private readonly ExternalAccessSettingsResolver $settingsResolver,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Cookie name and expire-on-close stay config-driven; the idle lifetime is admin-configurable.
        Config::set('session.cookie', config('external_access.session.cookie'));
        Config::set('session.lifetime', $this->settingsResolver->sessionIdleTimeoutMinutes());
        Config::set('session.expire_on_close', config('external_access.session.expire_on_close'));

        return $next($request);
    }
}
