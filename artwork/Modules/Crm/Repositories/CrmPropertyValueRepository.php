<?php

namespace Artwork\Modules\Crm\Repositories;

use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Illuminate\Database\Eloquent\Collection;

class CrmPropertyValueRepository
{
    public function getForContact(int $contactId): Collection
    {
        return CrmPropertyValue::where('crm_contact_id', $contactId)
            ->with('property.group')
            ->get();
    }

    public function updateOrCreate(int $contactId, int $propertyId, ?string $value): CrmPropertyValue
    {
        return CrmPropertyValue::updateOrCreate(
            [
                'crm_contact_id' => $contactId,
                'crm_property_id' => $propertyId,
            ],
            ['value' => $value]
        );
    }
}
