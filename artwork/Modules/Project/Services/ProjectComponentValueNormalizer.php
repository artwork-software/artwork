<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Illuminate\Validation\ValidationException;

/**
 * Einheitliche Prüfung der Projektwerte von Tab-Komponenten — intern (ProjectComponentValueController)
 * und extern (ExternalComponentValueService) gleich, damit beide Wege dieselbe Wertform speichern.
 * Gespeichert wird nur der zum Typ gehörende Schlüssel; fremde Schlüssel fallen weg.
 */
class ProjectComponentValueNormalizer
{
    /** Link-Ziele mit diesen Schemata würden beim Anklicken Code ausführen. */
    private const DANGEROUS_URL_SCHEME_PATTERN = '#^(javascript|data|vbscript):#i';

    private const DEFAULT_LINK_LIST_MAX_ITEMS = 20;

    private const MAX_TEXT_LENGTH = 65000;

    private const MAX_LINK_PART_LENGTH = 2048;

    /**
     * @param array<string, mixed>|null $data
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function normalize(Component $component, ?array $data): array
    {
        $data ??= [];

        return match (ProjectTabComponentEnum::tryFrom((string) $component->type)) {
            ProjectTabComponentEnum::TEXT_FIELD,
            ProjectTabComponentEnum::TEXT_AREA => ['text' => $this->text($data)],
            ProjectTabComponentEnum::LINK => ['text' => $this->linkTarget($this->text($data), 'data.text')],
            ProjectTabComponentEnum::CHECKBOX => ['checked' => $this->checked($data)],
            ProjectTabComponentEnum::DROPDOWN => ['selected' => $this->selected($component, $data)],
            ProjectTabComponentEnum::LINK_LIST => ['links' => $this->links($component, $data)],
            default => throw ValidationException::withMessages([
                'data' => __('This component has no editable value.'),
            ]),
        };
    }

    /**
     * Zahlen aus Zahlenfeldern bleiben erlaubt (werden zu Text), Listen/Objekte nicht.
     *
     * @param array<string, mixed> $data
     */
    private function text(array $data): string
    {
        $text = $data['text'] ?? '';

        if (!is_scalar($text)) {
            throw ValidationException::withMessages(['data.text' => __('validation.string', ['attribute' => 'text'])]);
        }

        $text = (string) $text;
        if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            throw ValidationException::withMessages([
                'data.text' => __('validation.max.string', ['attribute' => 'text', 'max' => self::MAX_TEXT_LENGTH]),
            ]);
        }

        return $text;
    }

    /**
     * Steuer- und Leerzeichen werden vor der Schema-Prüfung entfernt ("java\tscript:" o. ä.).
     */
    public static function isDangerousLinkTarget(string $url): bool
    {
        $compact = preg_replace('/[\x00-\x20]+/', '', $url) ?? '';

        return preg_match(self::DANGEROUS_URL_SCHEME_PATTERN, $compact) === 1;
    }

    private function linkTarget(string $url, string $errorKey): string
    {
        if (self::isDangerousLinkTarget($url)) {
            throw ValidationException::withMessages([$errorKey => __('validation.url', ['attribute' => 'url'])]);
        }

        return $url;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function checked(array $data): bool
    {
        $checked = filter_var($data['checked'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($checked === null) {
            throw ValidationException::withMessages([
                'data.checked' => __('validation.boolean', ['attribute' => 'checked']),
            ]);
        }

        return $checked;
    }

    /**
     * Leer = Auswahl aufgehoben. Sonst muss der Wert eine der in der Komponente hinterlegten Optionen sein.
     *
     * @param array<string, mixed> $data
     */
    private function selected(Component $component, array $data): ?string
    {
        $selected = $data['selected'] ?? null;

        // Älteres Frontend schickte die Option als Objekt
        if (is_array($selected) && array_key_exists('value', $selected)) {
            $selected = $selected['value'];
        }

        if ($selected === null || $selected === '') {
            return null;
        }

        if (!is_scalar($selected) || !in_array((string) $selected, $this->dropdownOptions($component), true)) {
            throw ValidationException::withMessages([
                'data.selected' => __('validation.in', ['attribute' => 'selected']),
            ]);
        }

        return (string) $selected;
    }

    /**
     * @return list<string>
     */
    private function dropdownOptions(Component $component): array
    {
        $options = [];
        foreach ((array) ($component->data['options'] ?? []) as $option) {
            $value = is_array($option) ? ($option['value'] ?? null) : $option;
            if (is_scalar($value) && (string) $value !== '') {
                $options[] = (string) $value;
            }
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $data
     * @return list<array{label: string, url: string}>
     */
    private function links(Component $component, array $data): array
    {
        $rows = $data['links'] ?? [];
        if (!is_array($rows)) {
            throw ValidationException::withMessages(['data.links' => __('validation.array', ['attribute' => 'links'])]);
        }

        $links = [];
        foreach (array_values($rows) as $index => $row) {
            $label = is_array($row) ? ($row['label'] ?? '') : null;
            $url = is_array($row) ? ($row['url'] ?? '') : null;

            if (!is_scalar($label) || !is_scalar($url)) {
                throw ValidationException::withMessages([
                    "data.links.$index" => __('validation.array', ['attribute' => 'link']),
                ]);
            }

            $label = trim((string) $label);
            $url = trim((string) $url);
            if ($label === '' && $url === '') {
                continue;
            }

            if (mb_strlen($label) > self::MAX_LINK_PART_LENGTH || mb_strlen($url) > self::MAX_LINK_PART_LENGTH) {
                throw ValidationException::withMessages([
                    "data.links.$index" => __('validation.max.string', [
                        'attribute' => 'link',
                        'max' => self::MAX_LINK_PART_LENGTH,
                    ]),
                ]);
            }

            $links[] = ['label' => $label, 'url' => $this->linkTarget($url, "data.links.$index.url")];
        }

        $maxItems = (int) ($component->data['max_items'] ?? self::DEFAULT_LINK_LIST_MAX_ITEMS);
        if ($maxItems > 0 && count($links) > $maxItems) {
            throw ValidationException::withMessages([
                'data.links' => __('validation.max.array', ['attribute' => 'links', 'max' => $maxItems]),
            ]);
        }

        return $links;
    }
}
