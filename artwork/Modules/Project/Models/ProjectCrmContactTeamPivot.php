<?php

namespace Artwork\Modules\Project\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectCrmContactTeamPivot extends Pivot
{
    protected $table = 'crm_contact_project_team';

    public $incrementing = true;

    protected $casts = [
        'roles' => 'array',
    ];
}
