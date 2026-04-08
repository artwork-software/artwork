<?php

namespace Artwork\Modules\Crm\Repositories;

use Artwork\Modules\Crm\Models\CrmProperty;
use Illuminate\Database\Eloquent\Collection;

class CrmPropertyRepository
{
    public function getByGroup(int $groupId): Collection
    {
        return CrmProperty::where('crm_property_group_id', $groupId)
            ->orderBy('sort_order')
            ->get();
    }

    public function getByContactType(int $typeId): Collection
    {
        return CrmProperty::whereHas('contactTypes', fn ($q) => $q->where('crm_contact_type_id', $typeId))
            ->with('group')
            ->orderBy('sort_order')
            ->get();
    }

    public function create(array $data): CrmProperty
    {
        return CrmProperty::create($data);
    }

    public function update(CrmProperty $property, array $data): bool
    {
        return $property->update($data);
    }

    public function delete(CrmProperty $property): ?bool
    {
        return $property->delete();
    }
}
