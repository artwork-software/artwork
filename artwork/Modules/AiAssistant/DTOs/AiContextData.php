<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von GET /api/v1/ai/context (core-api-context-projects; Konzept B1 §3).
 *
 * Bewusst ohne E-Mail-Adresse und ohne fremde Benutzer-IDs: der Kontext geht in den Modellkontext,
 * und personenbezogene Daten gehoeren dort nur hin, wenn sie fuer die Antwort gebraucht werden.
 */
class AiContextData extends Data
{
    /**
     * @param string[] $roles
     * @param string[] $permissions_summary
     */
    public function __construct(
        public AiContextUserData $user,
        public array $roles,
        public AiHouseData $house,
        public array $permissions_summary,
        // Nachtrag aus M3: sagt dem Modell, ob Aktionen ueberhaupt angeboten werden duerfen.
        public bool $level_c_enabled,
    ) {
    }
}
