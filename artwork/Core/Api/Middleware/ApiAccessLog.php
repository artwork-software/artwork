<?php

namespace Artwork\Core\Api\Middleware;

use Artwork\Core\Api\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Passport\AccessToken;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiAccessLog
{
    /**
     * Erlaubte Logeinträge pro Minute und IP für Requests, deren Authentifizierung fehlschlug.
     * Fehlgeschlagene Zugriffe sollen sichtbar bleiben, aber ein Skript, das Garbage-Bearer-Header
     * sprüht, darf die Tabelle nicht ungebremst fluten — throttle:machine-api greift erst NACH
     * auth:api und schützt diesen Pfad nicht.
     */
    private const UNAUTHENTICATED_LOGS_PER_MINUTE = 10;

    private const STARTED_AT_ATTRIBUTE = 'api_access_log_started_at';

    public function handle(Request $request, Closure $next): mixed
    {
        $request->attributes->set(self::STARTED_AT_ATTRIBUTE, microtime(true));

        return $next($request);
    }

    /**
     * Der eigentliche INSERT passiert in terminate(): Unter FPM läuft das nach dem Ausliefern der
     * Antwort und kostet den API-Konsumenten keine Latenz. Der Startzeitpunkt reist als
     * Request-Attribut mit, weil Laravel die Middleware für terminate() neu auflöst.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (!$this->hasBearerToken($request)) {
            return;
        }

        $passportTokenId = $this->resolveTokenId($request);

        if ($passportTokenId === null && !$this->allowUnauthenticatedLogEntry($request)) {
            return;
        }

        $startedAt = $request->attributes->get(self::STARTED_AT_ATTRIBUTE);

        try {
            ApiLog::create([
                'passport_token_id' => $passportTokenId,
                // Bewusst ohne Query-String: Das Log soll keine Nutzdaten aufnehmen.
                'url' => $request->url(),
                'method' => $request->method(),
                'ip' => $request->ip() ?? '',
                'user_agent' => $request->userAgent() ?? '',
                'response_status' => $response->getStatusCode(),
                'duration_ms' => is_float($startedAt)
                    ? (int) round((microtime(true) - $startedAt) * 1000)
                    : 0,
            ]);
        } catch (Throwable $throwable) {
            // Ein fehlschlagendes Protokoll darf die API-Antwort nicht verhindern.
            report($throwable);
        }
    }

    private function allowUnauthenticatedLogEntry(Request $request): bool
    {
        $key = 'api-log:unauth:' . ($request->ip() ?? 'unknown');

        if (RateLimiter::tooManyAttempts($key, self::UNAUTHENTICATED_LOGS_PER_MINUTE)) {
            return false;
        }

        RateLimiter::hit($key, 60);

        return true;
    }

    private function hasBearerToken(Request $request): bool
    {
        return $request->bearerToken() !== null;
    }

    /**
     * Die Token-Identität stammt aus dem Auth-Guard. Bei Cookie-Authentifizierung liefert Passport
     * einen TransientToken ohne Token-ID, bei fehlgeschlagener Authentifizierung gibt es gar keinen.
     */
    private function resolveTokenId(Request $request): ?string
    {
        $token = $request->user()?->token();

        return $token instanceof AccessToken ? $token->oauth_access_token_id : null;
    }
}
