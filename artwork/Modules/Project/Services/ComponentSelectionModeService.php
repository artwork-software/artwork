<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Support\ComponentSelection;
use Illuminate\Validation\ValidationException;

/**
 * Umschalten der Einstellung "Mehrfachauswahl erlauben" an DropDown-Komponenten.
 *
 * - Einschalten: bestehende Einzelwerte werden zu Listen mit einem Eintrag.
 * - Ausschalten: nur erlaubt, solange kein Projekt mehrere Werte gewählt hat; Listen werden wieder zu Einzelwerten.
 */
class ComponentSelectionModeService
{
    /**
     * Vor dem Speichern der neuen Komponentendaten aufrufen.
     *
     * @param array<string, mixed>|null $newData
     * @throws ValidationException
     */
    public function applyChange(Component $component, ?array $newData): void
    {
        if ($component->type !== ProjectTabComponentEnum::DROPDOWN->value) {
            return;
        }

        $wasMultiple = ComponentSelection::allowsMultiple($component->type, $component->data);
        $isMultiple = ComponentSelection::allowsMultiple($component->type, $newData);
        if ($wasMultiple === $isMultiple) {
            return;
        }

        $values = ProjectComponentValue::query()->where('component_id', $component->id)->get();

        if (!$isMultiple) {
            $projectsWithSeveralValues = $values->filter(
                static fn (ProjectComponentValue $value): bool => count(
                    ComponentSelection::values($value->data['selected'] ?? null)
                ) > 1
            )->count();

            if ($projectsWithSeveralValues > 0) {
                throw ValidationException::withMessages([
                    'data.multiple' => __(
                        'Multiple selection cannot be turned off while :count project(s) have several values selected.',
                        ['count' => $projectsWithSeveralValues]
                    ),
                ]);
            }
        }

        foreach ($values as $value) {
            $selected = ComponentSelection::values($value->data['selected'] ?? null);
            $data = $value->data ?? [];
            $data['selected'] = $isMultiple ? $selected : ($selected[0] ?? null);
            $value->update(['data' => $data]);
        }
    }
}
