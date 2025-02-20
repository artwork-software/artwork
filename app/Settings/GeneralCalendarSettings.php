<?php

namespace App\Settings;

use Carbon\Carbon;
use Spatie\LaravelSettings\Settings;

class GeneralCalendarSettings extends Settings
{

    public string $start;
    public string $end;

    public static function group(): string
    {
        return 'calendar';
    }
}