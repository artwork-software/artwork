<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * @property bool $enable_status
 * @property bool $enable_admission
 */
class EventSettings extends Settings
{

    public bool $enable_status;

    // Einlass-Feld (admission_time) instanzweit aktivieren
    public bool $enable_admission;

    public static function group(): string
    {
        return 'general';
    }
}
