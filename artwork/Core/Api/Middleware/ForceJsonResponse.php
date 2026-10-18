<?php

namespace Artwork\Core\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Die Maschinen-API antwortet immer mit JSON, auch wenn der Client keinen Accept-Header schickt.
 *
 * Ohne diese Angabe hält Laravel einen Aufruf für eine Browser-Anfrage: Ein fehlender Token endet
 * dann in einer Weiterleitung auf die Login-Seite statt in einem 401, ein Validierungsfehler in
 * einer Weiterleitung zurück statt in einer Fehlermeldung.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
