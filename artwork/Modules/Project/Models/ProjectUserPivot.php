<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Pivot;

class ProjectUserPivot extends Pivot
{
    protected $table = 'project_user';

    protected $casts = [
        'access_budget' => 'boolean',
        'is_manager' => 'boolean',
        'can_write' => 'boolean',
        'delete_permission' => 'boolean',
        'roles' => 'array',
    ];
}
