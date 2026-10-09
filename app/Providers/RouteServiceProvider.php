<?php

namespace App\Providers;

use Artwork\Modules\Project\Models\Project;
use Dedoc\Scramble\Scramble;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\AccessToken;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/dashboard';

    public function register(): void
    {
        parent::register();

        if (class_exists(Scramble::class)) {
            Scramble::ignoreDefaultRoutes();
        }
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

        if (config('app.force_https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        $this->routes(function (): void {
            // Vor routes/api.php registriert, damit die versionierten Pfade zuerst greifen.
            Route::prefix('api/v1')
                ->middleware('api.machine')
                ->group(base_path('routes/api_v1.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // External-access routes carry their own middleware groups (declared per-group in the files).
            Route::group([], base_path('routes/external-guest.php'));
            Route::group([], base_path('routes/external.php'));

            Route::prefix('api/app/v1')
                ->middleware('api.app')
                ->name('app.v1.')
                ->group(base_path('routes/app-api.php'));
        });

        Route::bind('projects', function ($value) {
            return Project::withTrashed()->whereKey($value)->firstOrFail();
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Login der App: nur drosseln, keine Sperren oder Alarme — die App probiert beim Login jede
        // bekannte Instanz durch, Fehlversuche gültiger Nutzer sind also normaler Verkehr.
        RateLimiter::for('app-login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(10)->by('app-login:ip:' . $ip),
                Limit::perMinute(5)->by('app-login:identity:' . hash('sha256', $email . '|' . $ip)),
            ];
        });

        RateLimiter::for('api-token', function (Request $request) {
            $token = $request->user()?->token();
            $key = $token instanceof AccessToken
                ? $token->oauth_access_token_id
                : $request->ip();

            return Limit::perMinute(120)->by($key);
        });
    }
}
