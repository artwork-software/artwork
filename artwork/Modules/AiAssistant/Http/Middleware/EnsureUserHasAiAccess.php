<?php

namespace Artwork\Modules\AiAssistant\Http\Middleware;

use Artwork\Modules\AiAssistant\Services\AiAssistantAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Web-Endpunkte mit KI-Bezug, die auch bei abgeschaltetem Haus erreichbar bleiben müssen
 * (Einstellungen, Session-Token): ohne KI-Freischaltung der Person (User::hasAiAccess) gibt es sie
 * nicht — 404, auch für Admins (ARTWORK-352).
 */
class EnsureUserHasAiAccess
{
    public function __construct(private readonly AiAssistantAccess $access)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->access->visibleTo($request->user()), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
