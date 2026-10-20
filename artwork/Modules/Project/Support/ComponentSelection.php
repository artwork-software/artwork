<?php

namespace Artwork\Modules\Project\Support;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;

/**
 * Auswahlwerte von Auswahl-Komponenten (DropDown, Mehrfachauswahl/CheckboxList).
 *
 * Gespeichert wird `selected` als Text (DropDown ohne Mehrfachauswahl) oder als Liste von Texten
 * (CheckboxList, DropDown mit "multiple"). Altbestand und ältere Clients können außerdem `{value: …}`
 * liefern. Alle Lesestellen (BI-Export, App, Druck, Übersicht …) gehen über diese Klasse, damit beide
 * Formen überall funktionieren.
 */
final class ComponentSelection
{
    /**
     * @param array<string, mixed>|null $componentData `components.data`
     */
    public static function allowsMultiple(?string $type, ?array $componentData): bool
    {
        return match (ProjectTabComponentEnum::tryFrom((string) $type)) {
            ProjectTabComponentEnum::CHECKBOX_LIST => true,
            ProjectTabComponentEnum::DROPDOWN => filter_var(
                $componentData['multiple'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            ),
            default => false,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(mixed $selected): array
    {
        if (is_array($selected) && array_key_exists('value', $selected)) {
            $selected = $selected['value'];
        }

        if ($selected === null || $selected === '') {
            return [];
        }

        if (!is_array($selected)) {
            return is_scalar($selected) ? [(string) $selected] : [];
        }

        $values = [];
        foreach ($selected as $entry) {
            if (is_array($entry) && array_key_exists('value', $entry)) {
                $entry = $entry['value'];
            }
            if (is_scalar($entry) && (string) $entry !== '' && !in_array((string) $entry, $values, true)) {
                $values[] = (string) $entry;
            }
        }

        return $values;
    }

    public static function displayText(mixed $selected, string $separator = ', '): string
    {
        return implode($separator, self::values($selected));
    }

    /**
     * Optionen einer Auswahl-Komponente als Liste von Texten.
     *
     * @param array<string, mixed>|null $componentData
     * @return list<string>
     */
    public static function options(?array $componentData): array
    {
        $options = [];
        foreach ((array) ($componentData['options'] ?? []) as $option) {
            $value = is_array($option) ? ($option['value'] ?? null) : $option;
            if (is_scalar($value) && (string) $value !== '') {
                $options[] = (string) $value;
            }
        }

        return $options;
    }
}
