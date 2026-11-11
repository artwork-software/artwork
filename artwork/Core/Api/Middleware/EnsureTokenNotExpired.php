<?php

namespace Artwork\Core\Api\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Erzwingt das Ablaufdatum, das die Oberflaeche bei der Vergabe eines Kunden-API-Keys setzt.
 *
 * Passport prueft beim Authentifizieren nur die Signatur, das JWT-`exp` und die Spalte `revoked` —
 * die Spalte `expires_at` in oauth_access_tokens liest es nie. Das Ablaufdatum aus der
 * Token-Verwaltung war deshalb bisher reine Anzeige: Der Key lief weiter, bis die globale
 * PAT-Laufzeit von einem Jahr erreicht war.
 *
 * Gehoert hinter auth:api, nie davor — vorher ist der Token noch nicht aufgeloest. Die
 * Middleware-Gruppe api.machine hat bewusst keine Auth; deshalb steht diese Middleware an den
 * auth:api-Reihen der Routendateien.
 */
class EnsureTokenNotExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        // AccessToken traegt nur die vier Claims aus dem Request; `expires_at` faellt per __get auf
        // den lazy geladenen Token-Record durch und ist damit die DB-Spalte, nicht das JWT-exp.
        $expiresAt = $request->user()?->token()?->expires_at;

        if ($expiresAt !== null && $expiresAt->isPast()) {
            return new JsonResponse(['error' => 'token_expired'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
