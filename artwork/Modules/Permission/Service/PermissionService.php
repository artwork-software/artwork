<?php

namespace Artwork\Modules\Permission\Service;

use Artwork\Modules\Permission\Repositories\PermissionRepository;
use Artwork\Modules\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    public function __construct(private readonly PermissionRepository $permissionRepository)
    {
    }

    public function save(Permission $permission): Permission
    {
        return $this->permissionRepository->save($permission);
    }

    public function create(
        string      $name,
        string      $guardName,
        string|null $nameDe,
        string|null $group,
        string|null $tooltipText,
        bool        $checked = false,
    ): Permission
    {
        return $this->createFromArray([
            'name' => $name,
            'guard_name' => $guardName,
            'name_de' => $nameDe,
            'group' => $group,
            'tooltipText' => $tooltipText,
            'checked' => $checked
        ]);
    }

    public function createFromArray(array $data): Permission
    {
        return $this->permissionRepository->createFromArray($data);
    }

    public function findByName(string $name): Permission|null
    {
        return $this->permissionRepository->getByName($name);
    }

    public function getTableFields(): array
    {
        return DB::select('DESCRIBE `permissions`');
    }
}
