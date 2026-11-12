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
 * Neben Instanz- und Hausschalter muss die Person das Konsolen-Recht "ai access" direkt halten
 * (User::hasAiAccess). Admin-Rolle allein reicht nicht.
 */
class AiAssistantAccess
{
    public function __construct(private readonly AiAssistantSettings $settings)
    {
    }

    public function allowed(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return (bool) config('ai.enabled') && $this->settings->enabled && $user->hasAiAccess();
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
