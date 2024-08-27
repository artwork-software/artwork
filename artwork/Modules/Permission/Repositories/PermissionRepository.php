<?php

namespace Artwork\Modules\Permission\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class PermissionRepository extends BaseRepository
{
    public function __construct(private readonly Permission $permission)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->permission->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->permission->newModelQuery();
    }

    public function getPermissionsGroupedByPermissionGroup(): Collection
    {
        return Permission::all()->groupBy('group');
    }

    public function getIdByName(string $name): null|int
    {
        return $this->getByName($name)?->id ?? null;
    }

    public function getByName(string $name): PermissionContract|null
    {
        try {
            return $this->getByNameOrFail($name);
        } catch (PermissionDoesNotExist $exception) {
            return null;
        }
    }

    public function getByNameOrFail(string $name): PermissionContract
    {
        return Permission::findByName($name);
    }

    public function createFromArray(array $data): PermissionContract
    {
        /** @var Permission $permission */
        $permission = Permission::create($data);

        return $permission;
    }

    public function getAll(): Collection
    {
        return $this->getNewModelQuery()->get();
    }
}
