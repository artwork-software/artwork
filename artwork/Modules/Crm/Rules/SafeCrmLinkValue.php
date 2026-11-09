<?php

namespace Artwork\Modules\Crm\Rules;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Project\Services\ProjectComponentValueNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * Link-Eigenschaften im CRM dürfen kein ausführbares Schema tragen (javascript:, data:, vbscript:),
 * gleiche Grenze wie bei Tab-Links und der Selbstbearbeitung durch Externe. Schemalose Werte wie
 * „www.example.org“ bleiben erlaubt; die Oberfläche verlinkt ohnehin nur http(s).
 *
 * Einsatz auf den Elementen einer Werteliste, deren Schlüssel die Property-ID ist
 * (`property_values.*` => [new SafeCrmLinkValue()]). Nicht-numerische Schlüssel und andere
 * Property-Typen werden übergangen.
 */
class SafeCrmLinkValue implements ValidationRule
{
    /** @var array<int, CrmProperty|null> */
    private array $properties = [];

    /**
     * @param Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || trim($value) === '') {
            return;
        }

        $propertyId = Str::afterLast($attribute, '.');
        if (!ctype_digit($propertyId)) {
            return;
        }

        $property = $this->property((int) $propertyId);

        if (self::isUnsafe($property, $value)) {
            $fail(__('validation.url', ['attribute' => $property->name]));
        }
    }

    /**
     * @phpstan-assert-if-true !null $property
     */
    private static function isUnsafe(?CrmProperty $property, mixed $value): bool
    {
        return $property?->type === CrmPropertyTypeEnum::LINK
            && is_string($value)
            && ProjectComponentValueNormalizer::isDangerousLinkTarget($value);
    }

    private function property(int $propertyId): ?CrmProperty
    {
        if (!array_key_exists($propertyId, $this->properties)) {
            $this->properties[$propertyId] = CrmProperty::query()->find($propertyId);
        }

        return $this->properties[$propertyId];
    }
}
