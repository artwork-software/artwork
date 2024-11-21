<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class EventSettings extends Settings
{

    public bool $enable_status;

    public static function group(): string
    {
        return 'general';
    }
}
