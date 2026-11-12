<?php

namespace Artwork\Modules\AiAssistant\Http\Middleware;

use Artwork\Modules\AiAssistant\Services\AiAssistantAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sperrt die Web-Endpunkte des KI-Assistenten, solange er nicht freigeschaltet ist
 * (core-chat-historie).
 *
 * Bewusst ueber AiAssistantAccess und nicht ueber ein Gate: Gate::before in
 * app/Providers/AuthServiceProvider.php gibt Artwork-Admins jede Gate-Abfrage frei — sie kaemen
 * sonst auch dann durch, wenn das Haus den Assistenten abgeschaltet hat.
 */
class EnsureAiAssistantIsEnabled
{
    public function __construct(private readonly AiAssistantAccess $access)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->access->allowed($request->user()), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
