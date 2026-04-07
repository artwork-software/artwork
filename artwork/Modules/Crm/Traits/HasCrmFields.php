<?php

namespace Artwork\Modules\Crm\Traits;

trait HasCrmFields
{
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