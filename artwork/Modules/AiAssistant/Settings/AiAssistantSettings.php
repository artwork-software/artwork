<?php

namespace Artwork\Modules\AiAssistant\Settings;

use Spatie\LaravelSettings\Settings;

class AiAssistantSettings extends Settings
{
    public bool $enabled;

    /** Stufe B — Datenauswertung (Lesezugriff des Assistenten auf Hausdaten). */
    public bool $level_b_enabled;

    /** Stufe C — Aktionen (schreibende Vorgaenge, immer mit Bestaetigung). */
    public bool $level_c_enabled;

    /** 0 = unbegrenzt. */
    public int $transcript_retention_days;

    /**
     * Client-ID des `ai-session`-OAuth-Clients, gesetzt von `ai:install`.
     * Nicht im Formular — der Wert ist Installationszustand, keine Hauseinstellung.
     */
    public ?string $ai_session_client_id;

    public static function group(): string
    {
        return 'ai_assistant';
    }
}
