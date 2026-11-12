<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiContextData;
use Artwork\Modules\AiAssistant\DTOs\AiContextUserData;
use Artwork\Modules\AiAssistant\DTOs\AiHouseData;
use Artwork\Modules\AiAssistant\Services\AiAssistantAccess;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Http\Request;

/**
 * GET /api/v1/ai/context — wer fragt, mit welchen Rechten, in welchem Haus
 * (core-api-context-projects; Konzept B1 §3).
 *
 * Enthaelt bewusst keine E-Mail-Adresse und keine fremden Benutzer-IDs. Die Rechte-Zusammenfassung
 * nennt nur die Bereiche, die fuer Stufe B ueberhaupt eine Rolle spielen — sie ist fuer das Modell
 * ein Hinweis darauf, welche Fragen sinnvoll sind, keine Autorisierung. Autorisiert wird bei jedem
 * Zugriff serverseitig ueber die Scopes (0.2 §7).
 */
class AiContextApiController extends Controller
{
    /** Rechte-Praefixe, die fuer die Lese-Endpunkte relevant sind (Brief B1a). */
    private const RELEVANT_PERMISSION_PREFIXES = [
        'projects',
        'project',
        'calendar',
        'shift',
        'shifts',
        'inventory',
        'room',
        'rooms',
    ];

    public function __invoke(
        Request $request,
        GeneralSettings $settings,
        AiAssistantAccess $access
    ): AiContextData {
        $user = $request->user();

        return new AiContextData(
            user: AiContextUserData::fromModel($user),
            roles: $user->getRoleNames()->values()->all(),
            house: new AiHouseData(
                name: $settings->business_name,
                timezone: (string) config('app.timezone'),
            ),
            permissions_summary: $this->relevantPermissions($user->getAllPermissions()->pluck('name')->all()),
            level_c_enabled: $access->levelCAllowed($user),
        );
    }

    /**
     * @param string[] $permissions
     * @return string[]
     */
    private function relevantPermissions(array $permissions): array
    {
        $relevant = array_filter(
            $permissions,
            function (string $permission): bool {
                foreach (self::RELEVANT_PERMISSION_PREFIXES as $prefix) {
                    if (str_contains($permission, $prefix)) {
                        return true;
                    }
                }

                return false;
            }
        );

        sort($relevant);

        return array_values($relevant);
    }
}
