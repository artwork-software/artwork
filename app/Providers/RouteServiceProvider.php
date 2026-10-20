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

        // Links und Weiterleitungen nie aus dem Host-Header bauen: sonst landen in synchron
        // versandten Mails (z. B. Login-Links) Adressen eines fremden Hosts. Eine lokale APP_URL
        // (häufig unverändert aus der .env.example) würde dagegen jede Weiterleitung kaputt machen.
        if ($this->app->isProduction() && $this->hasPublicAppUrl()) {
            \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
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

        // Login-Discovery der App: fragt jede bekannte Instanz nach einem E-Mail-Hash. Das
        // Limit bremst das Abklopfen fremder Adressen, normale Logins brauchen eine Anfrage.
        RateLimiter::for('app-discover', function (Request $request) {
            // Die App fragt pro Anmeldung einmal; die Stundengrenze bremst das Durchprobieren von Adressen.
            return [
                Limit::perMinute(10)->by('app-discover:ip:' . $request->ip()),
                Limit::perHour(60)->by('app-discover:ip-hour:' . $request->ip()),
            ];
        });

        RateLimiter::for('app-login', function (Request $request) {
            // Läuft vor der Request-Validierung: Bots posten "email" auch als Array (sonst 500)
            $email = $request->input('email');
            $email = Str::lower(is_string($email) ? $email : '');
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

    /**
     * Nur Loopback-/Entwicklungsadressen gelten als „nicht gesetzt“; Intranet-Namen und -IPs sind legitim.
     */
    private function hasPublicAppUrl(): bool
    {
        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        return $host !== ''
            && !in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true)
            && !str_ends_with($host, '.localhost')
            && !str_ends_with($host, '.test');
    }
}
