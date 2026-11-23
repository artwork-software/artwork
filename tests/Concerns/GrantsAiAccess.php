<?php

namespace Tests\Concerns;

use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\User\Models\User;

/**
 * Setzt die nur per Konsole vergebbare KI-Freischaltung (User::grantAiAccess), ohne die
 * Maschinen-API und KI-Routen gesperrt bzw. unsichtbar sind.
 */
trait GrantsAiAccess
{
    protected function grantAiAccess(User $user): User
    {
        $user->grantAiAccess();

        return $user;
    }

    /**
     * Schaltet den Assistenten fuer den Test ein: Betreiber-Schalter, Haus-Schalter, Stufe B und C.
     * Die KI-Routen pruefen das bei jeder Anfrage (EnsureAiHouseSwitches), nicht nur bei der
     * Token-Ausgabe.
     */
    protected function enableAiAssistant(): void
    {
        config(['ai.enabled' => true]);
        AiAssistantSettings::fake([
            'enabled' => true,
            'level_b_enabled' => true,
            'level_c_enabled' => true,
            'transcript_retention_days' => 0,
            'ai_session_client_id' => null,
        ]);
    }
}
