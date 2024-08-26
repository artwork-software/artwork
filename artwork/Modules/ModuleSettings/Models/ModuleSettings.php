<?php

namespace Artwork\Modules\ModuleSettings\Models;

use Spatie\LaravelSettings\Settings;

class ModuleSettings extends Settings
{
    public bool $projects;

    public bool $room_assignment;

    public bool $shift_plan;

    public bool $inventory;

    public bool $tasks;

    public bool $sources_of_funding;

    public bool $users;

    public bool $contracts;

    public static function group(): string
    {
        return 'module_settings';
    }
}
