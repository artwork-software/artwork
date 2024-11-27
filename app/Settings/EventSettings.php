<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * @property bool $enable_status
 */
class EventSettings extends Settings
{

    public bool $enable_status;

    public static function group(): string
    {
        return 'general';
    }
}
