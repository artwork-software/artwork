<?php

namespace Artwork\Modules\ExternalAccess\Http\Middleware;

use Artwork\Modules\ExternalAccess\Services\ExternalAccessSettingsResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Instanzweiter Feature-Schalter: Ist "Externe Zugänge" in den Einstellungen aus, existiert der
 * gesamte externe Bereich (Login, Dashboard, Tabs, CRM) nach außen nicht (404). Bereits vergebene
 * Zugänge bleiben in der Datenbank erhalten und funktionieren wieder, sobald das Feature aktiv ist.
 */
class EnsureExternalAccessEnabled
{
    public function __construct(
        private readonly ExternalAccessSettingsResolver $settingsResolver,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->settingsResolver->isEnabled()) {
            abort(404);
        }

        return $next($request);
    }
}
