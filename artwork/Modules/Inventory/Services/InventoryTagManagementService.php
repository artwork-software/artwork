<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Inventory\Models\InventoryTag;
use Artwork\Modules\Inventory\Models\InventoryTagGroup;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryTagManagementService
{

    public function getGroupsWithTags(): Collection
    {
        return InventoryTagGroup::query()
            ->with(['tags' => function ($q): void {
                $q->orderBy('position')->orderBy('name');
            }])
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    public function createGroup(array $data): InventoryTagGroup
    {
        return DB::transaction(function () use ($data) {
            $name = Arr::get($data, 'name');
            $position = Arr::get($data, 'position');

            if ($position === null) {
                $position = $this->getNextGroupPosition();
            }

            return InventoryTagGroup::create([
                'name'     => $name,
                'position' => $position,
            ]);
        });
    }

    public function updateGroup(InventoryTagGroup $group, array $data): InventoryTagGroup
    {
        return DB::transaction(function () use ($group, $data) {
            $payload = Arr::only($data, ['name', 'position']);

            // Falls Position nicht gesetzt ist: nicht anfassen
            if (! array_key_exists('position', $payload)) {
                unset($payload['position']);
            }

            $group->fill($payload);
            $group->save();

            return $group;
        });
    }

    public function reorderGroups(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                InventoryTagGroup::whereKey($id)->update([
                    'position' => $index + 1,
                ]);
            }
        });
    }

    public function createTag(array $data, array $userIds = [], array $departmentIds = []): InventoryTag
    {
        return DB::transaction(function () use ($data, $userIds, $departmentIds) {
            $hasRestricted = (bool) Arr::get($data, 'has_restricted_permissions', false);
            $groupId = Arr::get($data, 'inventory_tag_group_id');
            $position = Arr::get($data, 'position');

            if ($position === null) {
                $position = $this->getNextTagPosition($groupId);
            }

            /** @var InventoryTag $tag */
            $tag = InventoryTag::create([
                'name'                     => Arr::get($data, 'name'),
                'color'                    => Arr::get($data, 'color', '#000000'),
                'has_restricted_permissions' => $hasRestricted,
                'permission_mode'          => Arr::get($data, 'permission_mode', 'restricted_edit'),
                'inventory_tag_group_id'   => $groupId,
                'position'                 => $position,
            ]);

            if ($hasRestricted) {
                $this->syncTagPermissions($tag, $userIds, $departmentIds);
            }

            return $tag->load(['allowedUsers', 'allowedDepartments', 'group']);
        });
    }


    public function updateTag(
        InventoryTag $tag,
        array $data,
        array $userIds = [],
        array $departmentIds = []
    ): InventoryTag {
        return DB::transaction(function () use ($tag, $data, $userIds, $departmentIds) {
            $payload = Arr::only($data, [
                'name',
                'color',
                'has_restricted_permissions',
                'permission_mode',
                'inventory_tag_group_id',
                'position',
            ]);
            if (! array_key_exists('position', $payload)) {
                unset($payload['position']);
            }

            $tag->fill($payload);
            $tag->save();

            if ($tag->has_restricted_permissions) {
                $this->syncTagPermissions($tag, $userIds, $departmentIds);
            } else {
                $tag->allowedUsers()->detach();
                $tag->allowedDepartments()->detach();
            }

            return $tag->load(['allowedUsers', 'allowedDepartments', 'group']);
        });
    }

    public function syncTagPermissions(InventoryTag $tag, array $userIds, array $departmentIds): void
    {
        // IDs säubern (ints, unique)
        $userIds = collect($userIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $departmentIds = collect($departmentIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // optional: sicherstellen, dass es die IDs wirklich gibt
        $validUserIds = User::whereIn('id', $userIds)->pluck('id')->all();
        $validDepartmentIds = Department::whereIn('id', $departmentIds)->pluck('id')->all();

        $tag->allowedUsers()->sync($validUserIds);
        $tag->allowedDepartments()->sync($validDepartmentIds);
    }

    public function reorderTags(?InventoryTagGroup $group, array $orderedIds): void
    {
        DB::transaction(function () use ($group, $orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                InventoryTag::whereKey($id)->update([
                    'position' => $index + 1,
                ]);
            }
        });
    }

    protected function getNextGroupPosition(): int
    {
        $max = InventoryTagGroup::max('position');

        return $max ? $max + 1 : 1;
    }

    protected function getNextTagPosition(?int $groupId): int
    {
        $query = InventoryTag::query()
            ->when($groupId, function ($q) use ($groupId): void {
                $q->where('inventory_tag_group_id', $groupId);
            }, function ($q): void {
                $q->whereNull('inventory_tag_group_id');
            });

        $max = $query->max('position');

        return $max ? $max + 1 : 1;
    }
}
