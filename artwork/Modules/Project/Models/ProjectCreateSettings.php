<?php

namespace Artwork\Modules\Project\Models;

use Spatie\LaravelSettings\Settings;

class ProjectCreateSettings extends Settings
{

    public bool $attributes;
    public bool $state;
    public bool $managers;
    public bool $cost_center;
    public bool $budget_deadline;

    public static function group(): string
    {
        return 'project';
    }
}
