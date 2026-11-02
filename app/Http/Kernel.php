<?php

namespace App\Http;

use App\Http\Middleware\SetDeveloperEnvironment;
use App\Http\Middleware\UpdateUserStatus;
use Artwork\Core\Api\Middleware\ApiAccessLog;
use Artwork\Core\Api\Middleware\ForceJsonResponse;
use Artwork\Core\Api\Middleware\RejectTransientTokens;
use Artwork\Core\Http\Middleware\Authenticate;
use Artwork\Core\Http\Middleware\EncryptCookies;
use Artwork\Core\Http\Middleware\GuardInsecureDefaults;
use Artwork\Core\Http\Middleware\HandleInertiaRequests;
use Artwork\Core\Http\Middleware\Localization;
use Artwork\Core\Http\Middleware\PreventRequestsDuringMaintenance;
use Artwork\Core\Http\Middleware\RedirectIfAuthenticated;
use Artwork\Core\Http\Middleware\SecurityHeaders;
use Artwork\Core\Http\Middleware\TrimStrings;
use Artwork\Core\Http\Middleware\TrustProxies;
use Artwork\Core\Http\Middleware\VerifyCsrfToken;
use Artwork\Modules\ExternalAccess\Http\Middleware\Authenticate as ExternalAuthenticate;
use Artwork\Modules\ExternalAccess\Http\Middleware\CheckExternalAccessValid;
use Artwork\Modules\ExternalAccess\Http\Middleware\HandleExternalInertiaRequests;
use Artwork\Modules\ExternalAccess\Http\Middleware\RedirectIfAuthenticatedExternal;
use Artwork\Modules\ExternalAccess\Http\Middleware\SwapExternalSessionConfig;
use Artwork\Modules\ModuleSettings\Http\Middleware\ModuleSettingsMiddleware;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Jetstream\Http\Middleware\AuthenticateSession;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Throwable;

class Kernel extends HttpKernel
{
    protected $middleware = [
        // Führt defer()-Callbacks nach dem Senden der Antwort aus (Laravel-11-Standard, fehlte bei der
        // Laravel-10-Struktur) — ohne ihn liefen sie in Web-Requests nie.
        InvokeDeferredCallbacks::class,
        TrustProxies::class,
        GuardInsecureDefaults::class,
        HandleCors::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            SecurityHeaders::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            ModuleSettingsMiddleware::class,
            Localization::class,
            HandleInertiaRequests::class,
            SetDeveloperEnvironment::class,
            UpdateUserStatus::class,
        ],

        'api' => [
            EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            SubstituteBindings::class,
            ApiAccessLog::class,
        ],

        // Stack für die versionierte Maschinen-API (/api/v1). Bewusst ohne Sanctums
        // EnsureFrontendRequestsAreStateful — hier authentifiziert ausschließlich ein Bearer-Token,
        // keine Session. Ebenso ohne throttle:api, weil dessen 60/min pro Benutzer gelten und damit
        // das feinere Limit pro Token (throttle:api-token) überstimmen würden.
        'api.machine' => [
            ForceJsonResponse::class,
            RejectTransientTokens::class,
            SubstituteBindings::class,
            ApiAccessLog::class,
        ],

        // Stack der App (/api/app/v1): wie api.machine reines Bearer-Token-Auth, aber ohne
        // ApiAccessLog — das Protokoll gehört zu den Maschinen-Schlüsseln der Schnittstellen-Seite,
        // Geräte-Tokens tauchen dort nicht auf.
        'api.app' => [
            RejectTransientTokens::class,
            SubstituteBindings::class,
        ],

        // Isolated stack for external-access (magic-link) users.
        // Deliberately excludes AuthenticateSession, ModuleSettingsMiddleware, the standard
        // HandleInertiaRequests (which leaks permissions/roles), SetDeveloperEnvironment, and
        // UpdateUserStatus — all of those are internal-user concerns.
        'external' => [
            \Artwork\Modules\ExternalAccess\Http\Middleware\EnsureExternalAccessEnabled::class,
            SecurityHeaders::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            SwapExternalSessionConfig::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            ExternalAuthenticate::class,
            CheckExternalAccessValid::class,
            HandleExternalInertiaRequests::class,
            Localization::class,
        ],

        'external.guest' => [
            \Artwork\Modules\ExternalAccess\Http\Middleware\EnsureExternalAccessEnabled::class,
            SecurityHeaders::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            SwapExternalSessionConfig::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            RedirectIfAuthenticatedExternal::class,
            HandleExternalInertiaRequests::class,
            Localization::class,
        ],
    ];

    protected $routeMiddleware = [
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'password.confirm' => RequirePassword::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
        'role' => RoleMiddleware::class,
        'permission' => PermissionMiddleware::class,
        'role_or_permission' => RoleOrPermissionMiddleware::class,
        'shift-settings-area' => \App\Http\Middleware\EnsureShiftSettingsAreaPermission::class,
        'external.scoped' => \Artwork\Modules\ExternalAccess\Http\Middleware\EnsureScopedAccess::class,
        'external.crm' => \Artwork\Modules\ExternalAccess\Http\Middleware\EnsureCrmAccessActive::class,
    ];

    /**
     * Wie der Framework-Kernel, aber Fehler aus den Terminating-Callbacks (dispatch(...)->afterResponse(),
     * app()->terminating()) werden gemeldet statt geworfen: die Antwort ist bereits gesendet, eine Exception
     * endete sonst ebenfalls als "headers already sent"-Fatal. Nach einem Fehler entfallen die übrigen
     * Callbacks dieses Requests.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    public function terminate($request, $response): void
    {
        try {
            parent::terminate($request, $response);
        } catch (Throwable $throwable) {
            $this->requestStartedAt = null;
            report($throwable);
        }
    }

    /**
     * Wie der Framework-Kernel, aber jede Middleware einzeln abgesichert: terminate() läuft, nachdem die Antwort
     * gesendet wurde. Eine Exception hier (auch schon beim Neubau der Middleware, z. B. Passports CheckToken, das
     * den ResourceServer samt OAuth-Key-Prüfung braucht) endete sonst als "headers already sent"-Fatal, verdeckte
     * die Ursache und übersprang die übrigen terminate()-Aufrufe. Bei Laravel-Upgrades gegen
     * Illuminate\Foundation\Http\Kernel::terminateMiddleware() abgleichen.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    protected function terminateMiddleware($request, $response): void
    {
        $middlewares = $this->app->shouldSkipMiddleware() ? [] : array_merge(
            $this->gatherRouteMiddleware($request),
            $this->middleware
        );

        foreach ($middlewares as $middleware) {
            if (!is_string($middleware)) {
                continue;
            }

            try {
                [$name] = $this->parseMiddleware($middleware);

                $instance = $this->app->make($name);

                if (method_exists($instance, 'terminate')) {
                    $instance->terminate($request, $response);
                }
            } catch (Throwable $throwable) {
                report($throwable);
            }
        }
    }
}
