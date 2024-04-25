<?php

namespace Artwork\Modules\Permission\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Artwork\Modules\Permission\Models\Permission;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

readonly class PermissionRepository extends BaseRepository
{
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
        return Permission::create($data);
    }
}
