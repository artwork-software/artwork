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
        $cookie = (string) config('external_access.session.cookie');

        // Cookie name and expire-on-close stay config-driven; the idle lifetime is admin-configurable.
        Config::set('session.cookie', $cookie);
        Config::set('session.lifetime', $this->settingsResolver->sessionIdleTimeoutMinutes());
        Config::set('session.expire_on_close', config('external_access.session.expire_on_close'));

        // FALLE: Laravel instanziiert den Controller (Konstruktor-DI) VOR der Middleware-Pipeline, um
        // Controller-Middleware einzusammeln. Zieht eine Abhängigkeit dabei den Session-Store, ist er
        // schon mit dem internen Cookie-Namen gebaut und StartSession würde die WEB-Session laden.
        // Deshalb den bereits aufgelösten Store auf den externen Cookie-Namen umbenennen.
        if (app()->resolved('session.store')) {
            $store = app('session.store');
            if ($store->getName() !== $cookie) {
                $store->setName($cookie);
            }
        }

        return $next($request);
    }
}
