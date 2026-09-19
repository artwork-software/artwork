<?php

namespace Artwork\Modules\ExternalAccess\Http\Middleware;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Der CRM-Ablauf (crm_access_expires_at) gilt pro Route: Wer nur noch einen gültigen Tab-Zugang hat,
 * darf sich zwar einloggen (CheckExternalAccessValid), aber die eigenen CRM-Daten nicht mehr
 * einsehen oder ändern.
 */
class EnsureCrmAccessActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var ExternalAccess|null $external */
        $external = $request->user('external');

        if ($external === null || !$external->isCrmAccessActive()) {
            if ($request->expectsJson()) {
                abort(403, __('Your access to your CRM data has expired.'));
            }

            return redirect()
                ->route('external.dashboard')
                ->with('error', __('Your access to your CRM data has expired.'));
        }

        return $next($request);
    }
}
