<?php

namespace Artwork\Modules\Permission\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Permission\Models\Role;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

readonly class RoleRepository extends BaseRepository
{
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
        return Role::create($data);
    }
}
