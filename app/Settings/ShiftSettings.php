<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * @property bool $use_first_name_for_sort
 */
class ShiftSettings extends Settings
{
    public bool $use_first_name_for_sort;

    public static function group(): string
    {
        return 'shift-settings';
    }
}
