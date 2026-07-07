<?php

namespace Artwork\Modules\Crm\Repositories;

use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CrmPropertyGroupRepository
{
    public function getAll(): Collection
    {
        return CrmPropertyGroup::with(['properties.contactTypes', 'permissions.permissionable'])
            ->orderBy('sort_order')
            ->get();
    }

    public function getVisibleForUser(int $userId, array $departmentIds = [], bool $isCrmManager = false): Collection
    {
        $query = CrmPropertyGroup::with('properties')->orderBy('sort_order');

        if ($isCrmManager) {
            return $query->get();
        }

        return $query->where(function ($q) use ($userId, $departmentIds) {
            $q->where('is_confidential', false)
                ->orWhereHas('permissions', function ($sub) use ($userId, $departmentIds) {
                    $sub->where('can_view', true)
                        ->where(function ($morph) use ($userId, $departmentIds) {
                            $morph->where(function ($u) use ($userId) {
                                $u->where('permissionable_type', (new User())->getMorphClass())
                                    ->where('permissionable_id', $userId);
                            });
                            if (!empty($departmentIds)) {
                                $morph->orWhere(function ($d) use ($departmentIds) {
                                    $d->where('permissionable_type', (new Department())->getMorphClass())
                                        ->whereIn('permissionable_id', $departmentIds);
                                });
                            }
                        });
                });
        })->get();
    }

    public function create(array $data): CrmPropertyGroup
    {
        return CrmPropertyGroup::create($data);
    }

    public function update(CrmPropertyGroup $group, array $data): bool
    {
        return $group->update($data);
    }

    public function delete(CrmPropertyGroup $group): ?bool
    {
        return $group->delete();
    }
}
