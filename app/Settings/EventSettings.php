<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * @property bool $enable_status
 * @property bool $enable_admission
 * @property bool $always_direct_booking
 */
class EventSettings extends Settings
{

    public bool $enable_status;

    // Einlass-Feld (admission_time) instanzweit aktivieren
    public bool $enable_admission;

    // "Termine immer direkt buchbar": keine Raumbelegungsanfragen und keine
    // Terminverifizierung – jede Person mit Anlage-Recht bucht direkt
    public bool $always_direct_booking;

    public static function group(): string
    {
        return 'general';
    }
}
