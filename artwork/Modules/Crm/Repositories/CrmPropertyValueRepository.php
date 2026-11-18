<?php

namespace Artwork\Modules\Crm\Repositories;

use Artwork\Modules\Crm\Models\CrmPropertyValue;

class CrmPropertyValueRepository
{
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

    /**
     * Wert einer Upload-Eigenschaft: gespeicherter Pfad plus Anzeigename der Datei.
     */
    public function updateOrCreateFile(
        int $contactId,
        int $propertyId,
        ?string $path,
        ?string $fileName
    ): CrmPropertyValue {
        return CrmPropertyValue::updateOrCreate(
            [
                'crm_contact_id' => $contactId,
                'crm_property_id' => $propertyId,
            ],
            ['value' => $path, 'file_name' => $path === null ? null : $fileName]
        );
    }
}
