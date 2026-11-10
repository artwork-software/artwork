<?php

namespace Artwork\Core\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\TransientToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Passport erzeugt über POST oauth/token/refresh für jede Web-Session ein Cookie-Token
 * (TransientToken), das jeden Scope erfüllt. Maschinen-API und App sollen nur mit echten
 * Bearer-Tokens erreichbar sein – sonst liefe jeder Browser-Zugriff an Scopes und Protokoll vorbei.
 */
class RejectTransientTokens
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user('api')?->token() instanceof TransientToken) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
