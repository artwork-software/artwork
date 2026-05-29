<?php

namespace Artwork\Modules\ExternalAccess\Http\Middleware;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckExternalAccessValid
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var ExternalAccess|null $external */
        $external = Auth::guard('external')->user();

        if ($external === null || $external->revoked_at !== null || !$external->hasAnyActiveAccess()) {
            Auth::guard('external')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('external.login.form')
                ->with('status', __('Your access has expired or been revoked.'));
        }

        return $next($request);
    }
}
