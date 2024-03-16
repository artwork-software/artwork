<?php

namespace Artwork\Modules\Permission\Service;

use Artwork\Modules\Permission\Models\Role;
use Artwork\Modules\Permission\Repositories\RoleRepository;
use Artwork\Modules\Permission\Models\Permission;

class RoleService
{
    public function __construct(private readonly RoleRepository $roleRepository)
    {
    }

    public function save(Role $role): Role
    {
        return $this->roleRepository->save($role);
    }

    public function create(
        string      $name,
        string      $guardName,
        string|null $nameDe,
        string|null $tooltipText,
    ): Permission
    {
        return $this->createFromArray([
            'name' => $name,
            'guard_name' => $guardName,
            'name_de' => $nameDe,
            'tooltipText' => $tooltipText,
        ]);
    }

    public function createFromArray(array $data): Role
    {
        return $this->roleRepository->createFromArray($data);
    }

    public function findByName(string $name): Role|null
    {
        return $this->roleRepository->getByName($name);
    }
}
