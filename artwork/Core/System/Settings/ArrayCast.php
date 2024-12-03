<?php

namespace Artwork\Core\System\Settings;

use Spatie\LaravelSettings\SettingsCasts\SettingsCast;

class ArrayCast implements SettingsCast
{
    public function get($payload)
    {
        return json_decode($payload, true);
    }

    public function set($payload)
    {
        return json_encode($payload);
    }
}