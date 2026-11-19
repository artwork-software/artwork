<?php

namespace Artwork\Modules\Crm\Support;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Illuminate\Validation\ValidationException;

/**
 * Prüft Eigenschaftswerte eines Kontakts vor dem Speichern aus dem CRM heraus: Der Wert muss zum Typ der
 * Eigenschaft passen und Pflichtfelder des Kontakttyps dürfen nicht leer sein. Fehler landen
 * unter "property_values.<id>", wo die Formulare sie am Feld anzeigen.
 *
 * Datei-Eigenschaften bleiben außen vor: Sie werden nur über die Upload-Endpunkte gesetzt.
 */
final class CrmPropertyValueValidator
{
    /**
     * @param array<int|string, mixed> $propertyValues {propertyId: wert}
     * @param array<int, int>|null $requireFilledAmong Beim Anlegen: Eigenschaften, deren Pflicht-Kennzeichen auch
     *                                                 ohne übermittelten Wert gilt (die für die Person
     *                                                 bearbeitbaren). Null = nur übermittelte Werte prüfen.
     * @throws ValidationException
     */
    public function validate(
        CrmContactType $contactType,
        array $propertyValues,
        ?array $requireFilledAmong = null
    ): void
    {
        $typeProperties = $contactType->properties()->get()->keyBy('id');
        // Eigenschaften außerhalb des Typs (Altbestand) werden nur auf ihr Format geprüft
        $otherProperties = CrmProperty::query()
            ->whereKey(array_diff(array_map('intval', array_keys($propertyValues)), $typeProperties->keys()->all()))
            ->get()
            ->keyBy('id');
        $errors = [];

        foreach ($propertyValues as $propertyId => $value) {
            $property = $typeProperties->get((int) $propertyId) ?? $otherProperties->get((int) $propertyId);

            if ($property === null || $property->type === CrmPropertyTypeEnum::UPLOAD) {
                continue;
            }

            if ($this->isEmpty($property, $value)) {
                if ($property->pivot?->is_required) {
                    $errors['property_values.' . $propertyId] = __('This is a mandatory field.');
                }
                continue;
            }

            $message = $this->formatError($property, $value);
            if ($message !== null) {
                $errors['property_values.' . $propertyId] = $message;
            }
        }

        foreach ($requireFilledAmong ?? [] as $propertyId) {
            $property = $typeProperties->get((int) $propertyId);
            $isMissing = $property !== null
                && $property->pivot->is_required
                && $property->type !== CrmPropertyTypeEnum::UPLOAD
                && !array_key_exists($property->id, $propertyValues);

            if ($isMissing) {
                $errors['property_values.' . $property->id] = __('This is a mandatory field.');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function isEmpty(CrmProperty $property, mixed $value): bool
    {
        if ($property->type === CrmPropertyTypeEnum::CHECKBOX) {
            return !in_array($value, ['1', 1, true, 'true'], true);
        }

        if ($property->type === CrmPropertyTypeEnum::MULTISELECT) {
            return CrmPropertyValueFormat::decode($value) === [];
        }

        return $value === null || (is_scalar($value) && trim((string) $value) === '') || $value === [];
    }

    private function formatError(CrmProperty $property, mixed $value): ?string
    {
        if ($property->type !== CrmPropertyTypeEnum::MULTISELECT && !is_scalar($value)) {
            return __('This value is not valid.');
        }

        return match ($property->type) {
            CrmPropertyTypeEnum::NUMBER => is_numeric($value) ? null : __('Please enter a number.'),
            CrmPropertyTypeEnum::DATE => $this->isDate((string) $value) ? null : __('Please enter a valid date.'),
            CrmPropertyTypeEnum::SELECT => in_array((string) $value, CrmPropertyValueFormat::options($property), true)
                ? null
                : __('Please choose one of the available options.'),
            default => null,
        };
    }

    private function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
