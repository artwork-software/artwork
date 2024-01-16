<?php

namespace Artwork\Modules\Permission\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

class PermissionRepository extends BaseRepository
{
    public function getPermissionsGroupedByPermissionGroup(): Collection
    {
        return Permission::all()->groupBy('group');
    }
}
