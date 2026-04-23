<?php

namespace Artwork\Modules\Crm\Traits;

use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyValue;

trait HasCrmFields
{
    /**
     * Sync all CRM-mapped field values from this entity to its CRM contact property values.
     * Also syncs the display_name on the CRM contact.
     */
    public function syncToCrm(): void
    {
        // Try the direct FK relationship first, then fall back to polymorphic lookup
        $crmContact = null;

        if (method_exists($this, 'crmContact')) {
            $crmContact = $this->crmContact;
        }

        if (!$crmContact) {
            $crmContact = \Artwork\Modules\Crm\Models\CrmContact::where('entity_type', $this->getMorphClass())
                ->where('entity_id', $this->getKey())
                ->first();
        }

        if (!$crmContact) {
            return;
        }

        $fields = $this->getCrmFields();

        foreach ($fields as $propertyName => $mapping) {
            $value = $this->resolveCrmFieldValue($propertyName);

            $property = CrmProperty::where('name', $propertyName)->first();

            if (!$property) {
                continue;
            }

            CrmPropertyValue::updateOrCreate(
                [
                    'crm_contact_id' => $crmContact->id,
                    'crm_property_id' => $property->id,
                ],
                ['value' => $value]
            );
        }

        // Sync display name
        $displayName = $this->getCrmDisplayName();
        if ($displayName && $crmContact->display_name !== $displayName) {
            $crmContact->update(['display_name' => $displayName]);
        }
    }

    public function resolveCrmFieldValue(string $propertyName): ?string
    {
        $fields = $this->getCrmFields();

        if (!array_key_exists($propertyName, $fields)) {
            return null;
        }

        $mapping = $fields[$propertyName];

        if ($mapping instanceof \Closure) {
            return $mapping($this);
        }

        $value = $this->{$mapping};

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $value !== null ? (string) $value : null;
    }

    protected function getSharedCrmFields(): array
    {
        return [
            'Email' => 'email',
            'Telefon' => 'phone_number',
        ];
    }

    protected function getWorkProfileCrmFields(): array
    {
        return [
            'Work Name' => 'work_name',
            'Work Description' => 'work_description',
            'Stundensatz' => 'salary_per_hour',
            'Vergütungsbeschreibung' => 'salary_description',
            'Can Work Shifts' => fn($model) => $model->can_work_shifts ? '1' : '0',
        ];
    }

    public function setCrmFieldValue(string $propertyName, ?string $value): bool
    {
        $fields = $this->getCrmFields();

        if (!array_key_exists($propertyName, $fields)) {
            return false;
        }

        $mapping = $fields[$propertyName];

        if ($mapping instanceof \Closure) {
            return false;
        }

        $this->{$mapping} = $value;

        return $this->save();
    }

    protected function getAddressCrmFields(): array
    {
        return [
            'Straße, Hausnummer' => 'street',
            'PLZ' => 'zip_code',
            'Stadt' => 'location',
        ];
    }
}