<?php

namespace Artwork\Modules\AiAssistant\Providers;

use Artwork\Modules\AiAssistant\Contracts\ActionGuard;
use Artwork\Modules\AiAssistant\Contracts\BatchRecorder;
use Artwork\Modules\AiAssistant\Listeners\AttachActivityOrigin;
use Artwork\Modules\AiAssistant\Listeners\RevokeAiSessionTokens;
use Artwork\Modules\AiAssistant\Passport\AiSessionGrant;
use Artwork\Modules\AiAssistant\Passport\AiSessionTokenFactory;
use Artwork\Modules\AiAssistant\Support\DatabaseBatchRecorder;
use Artwork\Modules\AiAssistant\Support\PendingActionGuard;
use Artwork\Modules\AiAssistant\Support\SseEventSchemaValidator;
use DateInterval;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;

class AiAssistantServiceProvider extends ServiceProvider
{
    /** Konzept 0.2 §4: 15 Minuten, kein Refresh-Token. */
    private const TOKEN_TTL = 'PT15M';

    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__, 4) . '/config/ai.php',
            'ai'
        );

        $this->registerAiSessionAuthorizationServer();

        // Schema-Pruefung des Entwicklungs-Stubs (core-ki-bar-ui). Der Pfad kommt aus der Config,
        // deshalb eine explizite Bindung statt Autowiring.
        $this->app->singleton(SseEventSchemaValidator::class, static fn (): SseEventSchemaValidator
            => SseEventSchemaValidator::fromConfig());

        // Haken der Schreib-Endpunkte: seit core-confirm-actions traegt der ActionGuard die echte
        // Pruefkette (W2 §2), seit core-undo schreibt der BatchRecorder echte Batches (W3 §1).
        $this->app->bind(ActionGuard::class, PendingActionGuard::class);
        $this->app->bind(BatchRecorder::class, DatabaseBatchRecorder::class);
    }

    public function boot(): void
    {
        $this->registerRateLimiters();
        $this->registerListeners();
        $this->registerActivityOrigin();
    }

    /**
     * Eigener AuthorizationServer nur fuer die Ausstellung von KI-Session-Tokens.
     *
     * Sicherheitsrelevant (Befund A aus core-passport-verifikation): AiSessionGrant prueft — wie
     * Passports PersonalAccessGrant, dessen Spiegel er ist — kein Client-Secret. Am Singleton
     * AuthorizationServer waere er ueber das oeffentliche POST /oauth/token erreichbar, denn der
     * Grant-Treffer laeuft dort allein ueber den Request-Parameter grant_type; jeder koennte sich
     * mit grant_type=ai_session&user_id=<beliebig> ein Token fuer einen fremden User holen.
     *
     * Deshalb exakt das Muster, mit dem Passport denselben Fall fuer den personal_access-Grant
     * loest (vendor/laravel/passport/src/PassportServiceProvider.php:137-143): eine kontextuelle
     * Bindung, die nur greift, wenn AiSessionTokenFactory aufgeloest wird. Passports
     * makeAuthorizationServer() und makeCryptKey() sind protected, der Server wird daher hier
     * gleichwertig neu gebaut.
     */
    private function registerAiSessionAuthorizationServer(): void
    {
        $this->app->when(AiSessionTokenFactory::class)
            ->needs(AuthorizationServer::class)
            ->give(function (): AuthorizationServer {
                $server = new AuthorizationServer(
                    $this->app->make(Bridge\ClientRepository::class),
                    $this->app->make(Bridge\AccessTokenRepository::class),
                    $this->app->make(Bridge\ScopeRepository::class),
                    $this->privateKey(),
                    Passport::tokenEncryptionKey($this->app->make(Encrypter::class)),
                    new Bridge\PersonalAccessBearerTokenResponse()
                );

                $server->setDefaultScope(Passport::$defaultScope);

                // Die TTL haengt am Grant, nicht an der Factory (Passport-Muster).
                $server->enableGrantType(new AiSessionGrant(), new DateInterval(self::TOKEN_TTL));

                return $server;
            });
    }

    /** Gleiche Aufloesung wie PassportServiceProvider::makeCryptKey('private'). */
    private function privateKey(): CryptKey
    {
        $key = str_replace('\\n', "\n", config('passport.private_key') ?? '');

        if (!$key) {
            $key = 'file://' . Passport::keyPath('oauth-private.key');
        }

        return new CryptKey($key, null, Passport::$validateKeyPermissions);
    }

    /**
     * Konzept 0.2 §4: 10 Anfragen pro Minute und User. Muster: 'api-token' in
     * app/Providers/RouteServiceProvider.php, dort aber auf die Token-ID gekeyed — hier auf die
     * User-ID, weil der Endpunkt unter dem Web-Guard laeuft.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('ai-session', static fn (Request $request): Limit => Limit::perMinute(10)
            ->by((string) ($request->user()?->id ?? $request->ip())));
    }

    private function registerListeners(): void
    {
        Event::listen(Logout::class, RevokeAiSessionTokens::class);
        Event::listen(PasswordReset::class, RevokeAiSessionTokens::class);
    }

    /**
     * Herkunft (`via`) an jedem Activity-Log-Eintrag (core-via-activity-log, Konzept C1 §6).
     *
     * Haengt am konfigurierten Activity-Modell, nicht an einer festen Klasse — config/activitylog.php
     * darf das Modell austauschen, ohne dass dieser Provider bricht.
     */
    private function registerActivityOrigin(): void
    {
        $model = config('activitylog.activity_model');

        if (!is_string($model) || !class_exists($model)) {
            return;
        }

        $model::creating(static fn ($activity) => app(AttachActivityOrigin::class)->handle($activity));
    }
}
