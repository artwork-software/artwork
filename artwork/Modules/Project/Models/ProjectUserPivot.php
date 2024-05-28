<?php

namespace Artwork\Modules\Project\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectUserPivot extends Pivot
{
    protected $casts = [
        'access_budget' => 'boolean',
        'is_manager' => 'boolean',
        'can_write' => 'boolean',
        'delete_permission' => 'boolean',
    ];
}
