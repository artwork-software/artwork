<?php

namespace Artwork\Core\Api\Middleware;

use Artwork\Modules\User\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zusätzliche Schranke hinter auth:api vor den KI-Routen (/api/v1/ai/*): Der Besitzer des Tokens
 * muss das nur per Konsole vergebbare Recht "ai access" direkt halten (User::hasAiAccess). Scopes
 * regeln, WAS ein Token darf; dieses Recht regelt, OB dessen Besitzer die KI-Schnittstelle überhaupt
 * nutzen darf. Ein Entzug wirkt sofort auf alle bestehenden KI-Tokens der Person. Die übrigen
 * Maschinen-Routen (Inventar, Raumbelegung) laufen bewusst ohne diese Schranke.
 *
 * Muss nach auth:api laufen, sonst ist der Token-Besitzer noch nicht aufgelöst.
 */
class EnsureAiAccessPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('api');

        if (!$user instanceof User || !$user->hasAiAccess()) {
            return response()->json(
                ['message' => 'This token owner is not permitted to use the API.'],
                Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
