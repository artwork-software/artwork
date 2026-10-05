<?php

namespace Artwork\Modules\Crm\Services;

use Artwork\Modules\Crm\Exceptions\CrmSettingLockedException;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Repositories\CrmPropertyRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class CrmPropertyService
{
    public function __construct(
        private CrmPropertyRepository $repository,
    ) {
    }

    public function getByGroup(int $groupId): Collection
    {
        return $this->repository->getByGroup($groupId);
    }

    public function getByContactType(int $typeId): Collection
    {
        return $this->repository->getByContactType($typeId);
    }

    public function reorder(array $properties): void
    {
        foreach ($properties as $item) {
            CrmProperty::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }
    }

    public function store(array $data): CrmProperty
    {
        return $this->repository->create($data);
    }

    public function update(CrmProperty $property, array $data): bool
    {
        // Systemeigenschaften werden im Code über Name/Typ gefunden (E-Mail-Duplikate, Tooltip)
        $renamesOrRetypes = (isset($data['name']) && $data['name'] !== $property->name)
            || (isset($data['type']) && $data['type'] !== $property->type?->value);
        if ($property->is_system && $renamesOrRetypes) {
            throw new CrmSettingLockedException(__('System properties cannot be renamed or changed in type.'));
        }

        return $this->repository->update($property, $data);
    }

    public function destroy(CrmProperty $property): void
    {
        if ($property->is_system) {
            throw new CrmSettingLockedException(__('System properties cannot be deleted.'));
        }

        $this->repository->delete($property);
    }

    public function assignToTypes(CrmProperty $property, array $typeIds): void
    {
        $property->contactTypes()->sync($typeIds);
    }
}
