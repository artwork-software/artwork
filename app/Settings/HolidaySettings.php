<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * @property array $subdivisions
 * @property bool $public_holidays
 * @property bool $school_holidays
 */
class HolidaySettings extends Settings
{

    public array $subdivisions;
    public bool $public_holidays;
    public bool $school_holidays;

    public static function group(): string
    {
        return 'holiday';
    }
}
