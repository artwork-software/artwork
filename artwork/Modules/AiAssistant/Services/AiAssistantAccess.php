<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\User\Models\User;

/**
 * Einzige Freischaltungsregel des KI-Assistenten (core-modul-settings-policy).
 *
 * Bewusst kein Gate und keine Policy: `Gate::before` in App\Providers\AuthServiceProvider gibt
 * Artwork-Admins jede Gate-Abfrage als true zurueck. Ein Gate `ai-assistant.use` waere damit auch
 * fuer Admins true, waehrend das Haus den Assistenten abgeschaltet hat. Alle Konsumenten
 * (Shared-Props, Session-Endpunkt, Bar) rufen diesen Service direkt.
 *
 * Vor allem steht die KI-Freischaltung der Person (User::hasAiAccess, nur per Konsole vergebbar):
 * ohne sie existiert der Assistent für diese Person nicht — keine Bar, keine Einstellungen,
 * kein KI-Hinweis, auch nicht für Admins (ARTWORK-352).
 */
class AiAssistantAccess
{
    public function __construct(private readonly AiAssistantSettings $settings)
    {
    }

    /**
     * Ob die Person überhaupt etwas vom Assistenten sehen darf — unabhängig davon, ob das Haus ihn
     * eingeschaltet hat (die Einstellungsseite muss auch bei abgeschaltetem Haus erreichbar sein).
     */
    public function visibleTo(?User $user): bool
    {
        return $user !== null && $user->hasAiAccess();
    }

    public function allowed(?User $user): bool
    {
        if (!$this->visibleTo($user)) {
            return false;
        }

        return (bool) config('ai.enabled') && $this->settings->enabled;
    }

    /** Stufe B — Datenauswertung. Nur sinnvoll, wenn der Assistent ueberhaupt frei ist. */
    public function levelBAllowed(?User $user): bool
    {
        return $this->allowed($user) && $this->settings->level_b_enabled;
    }

    /** Stufe C — Aktionen. Setzt Stufe B voraus (erzwungen im FormRequest der Einstellungen). */
    public function levelCAllowed(?User $user): bool
    {
        return $this->levelBAllowed($user) && $this->settings->level_c_enabled;
    }
}
