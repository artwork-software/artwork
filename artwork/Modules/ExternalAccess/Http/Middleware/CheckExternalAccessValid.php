<?php

namespace Artwork\Modules\ExternalAccess\Http\Middleware;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessSettingsResolver;
use Artwork\Modules\ExternalAccess\Services\ExternalLoginService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckExternalAccessValid
{
    public function __construct(
        private readonly ExternalAccessSettingsResolver $settingsResolver,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var ExternalAccess|null $external */
        $external = Auth::guard('external')->user();

        if (
            $external === null
            || $external->revoked_at !== null
            || !$external->hasAnyActiveAccess()
            || $this->absoluteLifetimeExceeded($request)
        ) {
            Auth::guard('external')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('external.login.form')
                ->with('status', __('Your access has expired or been revoked.'));
        }

        return $next($request);
    }

    /**
     * Absolute Session-Lebensdauer (Einstellung session_absolute_lifetime_minutes): Sitzungen ohne
     * Login-Zeitstempel (Altbestand) laufen weiter, bis das Idle-Timeout greift.
     */
    private function absoluteLifetimeExceeded(Request $request): bool
    {
        $loginAt = $request->session()->get(ExternalLoginService::SESSION_LOGIN_AT_KEY);
        if ($loginAt === null) {
            return false;
        }

        $lifetimeSeconds = $this->settingsResolver->sessionAbsoluteLifetimeMinutes() * 60;

        return (now()->timestamp - (int) $loginAt) > $lifetimeSeconds;
    }
}
