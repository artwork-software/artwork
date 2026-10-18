<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Pivot;

/**
 * @property bool $access_budget
 * @property bool $is_manager
 * @property bool $can_write
 * @property bool $delete_permission
 * @property list<int>|null $roles
 */
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
