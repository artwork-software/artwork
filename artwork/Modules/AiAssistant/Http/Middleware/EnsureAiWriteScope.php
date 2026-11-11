<?php

namespace Artwork\Modules\AiAssistant\Http\Middleware;

use Artwork\Modules\AiAssistant\Support\AiScopes;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scope-Pruefung der Schreib-Endpunkte (core-confirm-actions, Nachtrag aus `ai-write-tools-confirm`).
 *
 * Die Vorschau (`dry_run=true`, der Default) darf auch ein Read-Token holen: artwork-ai hat vor der
 * Bestaetigung nur eines, und die Vorschau schreibt nichts — sie laeuft in einer Transaktion, die
 * zurueckgerollt wird. Sobald `dry_run=false` gesetzt ist, ist `ai:write` Pflicht; das Token dafuer
 * gibt es nur mit einer bestaetigten Aktion (W2 §2).
 */
class EnsureAiWriteScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isDryRun = $request->boolean('dry_run', true);

        $allowed = $user !== null && (
            $user->tokenCan(AiScopes::WRITE)
            || ($isDryRun && $user->tokenCan(AiScopes::READ))
        );

        abort_unless($allowed, Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
