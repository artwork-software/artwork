<?php

namespace Artwork\Modules\Permission\Services;

use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Permission\Repositories\PermissionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

readonly class PermissionService
{
    public function __construct(private PermissionRepository $permissionRepository)
    {
    }

    public function save(Permission $permission): Permission
    {
        /** @var Permission $permission */
        $permission = $this->permissionRepository->save($permission);

        return $permission;
    }

    public function createFromArray(array $data): Permission
    {
        /** @var Permission $permission */
        $permission = $this->permissionRepository->createFromArray($data);

        return $permission;
    }

    public function findByName(string $name): Permission|null
    {
        /** @var Permission $permission */
        $permission = $this->permissionRepository->getByName($name);

        return $permission;
    }

    /**
     * @return array<mixed, mixed>
     */
    public function getTableFields(): array
    {
        return DB::select('DESCRIBE `permissions`');
    }

    public function getAll(): Collection
    {
        return $this->permissionRepository->getAll();
    }

    public function getAllPermissionNames(): SupportCollection
    {
        return $this->getAll()->pluck('name');
    }
}
