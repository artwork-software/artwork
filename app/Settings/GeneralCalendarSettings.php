<?php

namespace App\Settings;

use Carbon\Carbon;
use Spatie\LaravelSettings\Settings;

class GeneralCalendarSettings extends Settings
{

    public string $start;
    public string $end;

    // Tagesbemerkungen: instanzweite Aktivierung der Spalte + Pflicht-Anzeige
    // (true = User können die Spalte nicht über die Anzeigeeinstellungen abwählen)
    public bool $day_remarks_enabled;
    public bool $day_remarks_mandatory;

    public static function group(): string
    {
        return 'calendar';
    }
}
