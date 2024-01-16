<?php

namespace Artwork\Modules\Permission\Repositories;

use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Repository\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Throwable;

class PermissionRepository extends BaseRepository
{
    public function getPermissionsGroupedByPermissionGroup(): Collection
    {
        return Permission::all()->groupBy('group');
    }

    public function getIdByName(string $name): null|int
    {
        try {
            return Permission::findByName($name)->id;
        } catch (Throwable $t) {
            return null;
        }
    }
}
