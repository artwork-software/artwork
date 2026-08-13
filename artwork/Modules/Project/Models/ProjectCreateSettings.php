<?php

namespace Artwork\Modules\Project\Models;

use Spatie\LaravelSettings\Settings;

class ProjectCreateSettings extends Settings
{

    public bool $attributes;
    public bool $state;
    public bool $state_required;
    public bool $managers;
    public bool $cost_center;
    public bool $budget_deadline;
    public bool $show_artists;
    public bool $crm_contacts_in_team;

    public static function group(): string
    {
        return 'project';
    }
}
