<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * @property bool $use_first_name_for_sort
 * @property bool $calendar_abo_show_all_shifts
 * @property bool $allow_shift_overbooking
 * @property bool $granular_permissions_enabled
 * @property bool $hide_uncommitted_shifts_from_own_roster
 */
class ShiftSettings extends Settings
{
    public bool $use_first_name_for_sort;

    public bool $calendar_abo_show_all_shifts;

    public bool $allow_shift_overbooking;

    public bool $granular_permissions_enabled;

    // Einmal-Flag: granulare Default-Rechte wurden bereits an die Inhaber der
    // Master-Permission verteilt — verhindert, dass jedes erneute Aktivieren
    // individuell entzogene Rechte wieder zurückbringt.
    public bool $granular_defaults_granted;

    public bool $hide_uncommitted_shifts_from_own_roster;

    public static function group(): string
    {
        return 'shift-settings';
    }
}
