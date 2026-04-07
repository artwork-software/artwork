<?php

namespace Artwork\Modules\Crm\Services;

use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Repositories\CrmPropertyGroupRepository;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

readonly class CrmPropertyGroupService
{
    public function __construct(
        private CrmPropertyGroupRepository $repository,
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function getVisibleForUser(int $userId, array $departmentIds = [], bool $isCrmManager = false): Collection
    {
        return $this->repository->getVisibleForUser($userId, $departmentIds, $isCrmManager);
    }

    public function store(array $data): CrmPropertyGroup
    {
        return $this->repository->create($data);
    }

    public function update(CrmPropertyGroup $group, array $data): bool
    {
        return $this->repository->update($group, $data);
    }

    public function destroy(CrmPropertyGroup $group): void
    {
        if ($group->is_system) {
            throw new \RuntimeException('System property groups cannot be deleted.');
        }

        $this->repository->delete($group);
    }

    public function annotateEditPermissions(Collection $groups, int $userId, array $departmentIds, bool $isCrmManager): void
    {
        // Eager-load permissions to avoid N+1
        $groups->loadMissing('permissions');

        $userMorphClass = (new User())->getMorphClass();
        $departmentMorphClass = (new Department())->getMorphClass();

        foreach ($groups as $group) {
            if ($isCrmManager || !$group->is_confidential) {
                $group->setAttribute('can_edit', true);
                continue;
            }

            $canEdit = $group->permissions->contains(function ($perm) use ($userId, $departmentIds, $userMorphClass, $departmentMorphClass) {
                if (!$perm->can_edit) {
                    return false;
                }

                if ($perm->permissionable_type === $userMorphClass && $perm->permissionable_id === $userId) {
                    return true;
                }

                if ($perm->permissionable_type === $departmentMorphClass && in_array($perm->permissionable_id, $departmentIds)) {
                    return true;
                }

                return false;
            });

            $group->setAttribute('can_edit', $canEdit);
        }
    }

    public function getEditablePropertyIds(int $userId, array $departmentIds, bool $isCrmManager): array
    {
        $groups = $this->getVisibleForUser($userId, $departmentIds, $isCrmManager);
        $groups->loadMissing('properties');
        $this->annotateEditPermissions($groups, $userId, $departmentIds, $isCrmManager);

        return $groups
            ->where('can_edit', true)
            ->flatMap(fn ($g) => $g->properties)
            ->pluck('id')
            ->toArray();
    }

    public function getVisiblePropertyIds(int $userId, array $departmentIds, bool $isCrmManager): array
    {
        $groups = $this->getVisibleForUser($userId, $departmentIds, $isCrmManager);
        $groups->loadMissing('properties');

        return $groups
            ->flatMap(fn ($g) => $g->properties)
            ->pluck('id')
            ->toArray();
    }

    public function updatePermissions(CrmPropertyGroup $group, array $permissions): void
    {
        DB::transaction(function () use ($group, $permissions) {
            $group->permissions()->delete();

            foreach ($permissions as $permission) {
                $group->permissions()->create([
                    'permissionable_type' => $permission['permissionable_type'],
                    'permissionable_id' => $permission['permissionable_id'],
                    'can_view' => $permission['can_view'] ?? false,
                    'can_edit' => $permission['can_edit'] ?? false,
                ]);
            }
        });
    }
}
