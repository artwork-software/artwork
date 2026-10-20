<?php

namespace Artwork\Modules\Crm\Support;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmProperty;

/**
 * Speicherform der CRM-Eigenschaftswerte (crm_property_values.value, TEXT).
 *
 * Mehrfachauswahl (multiselect) speichert eine JSON-Liste der gewählten Optionen in Reihenfolge der
 * Optionen; alle anderen Typen bleiben Text. Ein Nicht-JSON-Text bei einer Mehrfachauswahl (z. B. aus einer
 * umgestellten Einfachauswahl) zählt als Liste mit einem Eintrag. Lesestellen (Anzeige, Export, Filter)
 * gehen über diese Klasse.
 */
final class CrmPropertyValueFormat
{
    public const IMPORT_SEPARATOR = ';';

    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    public static function isMultiple(CrmProperty|CrmPropertyTypeEnum|string|null $type): bool
    {
        if ($type instanceof CrmProperty) {
            $type = $type->type;
        }

        $type = $type instanceof CrmPropertyTypeEnum ? $type : CrmPropertyTypeEnum::tryFrom((string) $type);

        return $type === CrmPropertyTypeEnum::MULTISELECT;
    }

    /**
     * Wert für die Datenbank: Mehrfachauswahl als JSON-Liste (nur hinterlegte Optionen, leer = null),
     * sonst Text wie bisher.
     */
    public static function toStorage(?CrmProperty $property, mixed $value): ?string
    {
        if ($property === null || !self::isMultiple($property)) {
            if ($value === null) {
                return null;
            }

            return is_array($value) ? self::encode(self::flatten($value)) : (string) $value;
        }

        $selected = self::filterToOptions($property, self::decode($value));

        return $selected === [] ? null : self::encode($selected);
    }

    /**
     * Gewählte Optionen einer Mehrfachauswahl; akzeptiert Liste, JSON-Text oder Einzeltext.
     *
     * @return list<string>
     */
    public static function decode(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) && array_is_list($decoded) ? $decoded : [$value];
        }

        return is_array($value) ? self::flatten($value) : (is_scalar($value) ? [(string) $value] : []);
    }

    public static function displayText(CrmProperty|CrmPropertyTypeEnum|string|null $type, ?string $value): string
    {
        if (self::isMultiple($type)) {
            return implode(', ', self::decode($value));
        }

        return (string) $value;
    }

    /**
     * Import: Optionen getrennt durch ";" – jede muss eine hinterlegte Option sein, sonst false.
     */
    public static function fromImport(CrmProperty $property, string $raw): string|false
    {
        $parts = array_values(array_filter(
            array_map('trim', explode(self::IMPORT_SEPARATOR, $raw)),
            static fn (string $part): bool => $part !== ''
        ));
        $options = self::options($property);

        foreach ($parts as $part) {
            if (!in_array($part, $options, true)) {
                return false;
            }
        }

        $selected = self::filterToOptions($property, $parts);

        return $selected === [] ? false : self::encode($selected);
    }

    /**
     * SQL-LIKE-Muster, das eine Option in der gespeicherten JSON-Liste findet (exakt, inkl. Anführungszeichen).
     */
    public static function likePatternFor(string $option): string
    {
        $needle = (string) json_encode($option, self::JSON_FLAGS);

        return '%' . addcslashes($needle, '%_\\') . '%';
    }

    /**
     * @return list<string>
     */
    public static function options(CrmProperty $property): array
    {
        return array_values(array_map(
            'strval',
            array_filter((array) ($property->select_values ?? []), static fn ($option): bool => is_scalar($option))
        ));
    }

    /**
     * @param list<string> $values
     */
    public static function encode(array $values): string
    {
        return (string) json_encode(array_values($values), self::JSON_FLAGS);
    }

    /**
     * @param list<string> $selected
     * @return list<string>
     */
    private static function filterToOptions(CrmProperty $property, array $selected): array
    {
        return array_values(array_filter(
            self::options($property),
            static fn (string $option): bool => in_array($option, $selected, true)
        ));
    }

    /**
     * @param array<mixed> $values
     * @return list<string>
     */
    private static function flatten(array $values): array
    {
        $result = [];
        foreach ($values as $entry) {
            if (is_scalar($entry) && (string) $entry !== '' && !in_array((string) $entry, $result, true)) {
                $result[] = (string) $entry;
            }
        }

        return $result;
    }
}
