<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * @property bool $use_first_name_for_sort
 * @property bool $calendar_abo_show_all_shifts
 */
class ShiftSettings extends Settings
{
    public bool $use_first_name_for_sort;

    public bool $calendar_abo_show_all_shifts;

    public static function group(): string
    {
        return 'shift-settings';
    }
}
