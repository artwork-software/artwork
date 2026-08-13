<?php

namespace Artwork\Core\Api\Middleware;

use Artwork\Core\Api\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\AccessToken;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Protokolliert Zugriffe auf die Maschinen-API.
 *
 * Protokolliert wird nach der Verarbeitung, damit Statuscode und Dauer mitgeschrieben werden können.
 * Erfasst werden ausschließlich Requests mit Bearer-Token — Session-Requests der Oberfläche laufen
 * durch dieselbe Middleware-Gruppe, gehören aber nicht in dieses Log.
 *
 * Fehlgeschlagene Authentifizierungen werden bewusst mitgeschrieben (ohne Token-Bezug), weil genau
 * die für die Fehlersuche und zum Erkennen von Missbrauch gebraucht werden.
 */
class ApiAccessLog
{
    public function handle(Request $request, Closure $next): mixed
    {
        $startedAt = microtime(true);

        $response = $next($request);

        if ($this->hasBearerToken($request)) {
            $this->writeLog($request, $response, $startedAt);
        }

        return $response;
    }

    private function hasBearerToken(Request $request): bool
    {
        return $request->bearerToken() !== null;
    }

    private function writeLog(Request $request, Response $response, float $startedAt): void
    {
        try {
            ApiLog::create([
                'passport_token_id' => $this->resolveTokenId($request),
                // Bewusst ohne Query-String: Das Log soll keine Nutzdaten aufnehmen.
                'url' => $request->url(),
                'method' => $request->method(),
                'ip' => $request->ip() ?? '',
                'user_agent' => $request->userAgent() ?? '',
                'response_status' => $response->getStatusCode(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
        } catch (Throwable $throwable) {
            // Ein fehlschlagendes Protokoll darf die API-Antwort nicht verhindern.
            report($throwable);
        }
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
