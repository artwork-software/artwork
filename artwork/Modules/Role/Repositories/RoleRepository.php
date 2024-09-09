<?php

namespace Artwork\Modules\Role\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Role\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class RoleRepository extends BaseRepository
{
    public function __construct(private readonly Role $role)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->role->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->role->newModelQuery();
    }

    public function getByName(string $name): RoleContract|null
    {
        try {
            return $this->getByNameOrFail($name);
        } catch (RoleDoesNotExist $exception) {
            return null;
        }
    }

    public function getByNameOrFail(string $name): RoleContract
    {
        return Role::findByName($name);
    }

    public function createFromArray(array $data): RoleContract
    {
        /** @var Role $role */
        $role = Role::create($data);

        return $role;
    }

    public function getAll(): Collection
    {
        return $this->getNewModelQuery()->get();
    }
}
