<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ProjectPrintLayout;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Drei kuratierte Drucklayouts (nur PRINTABLE-Komponenten).
 * Idempotent: existierende Layouts gleichen Namens bleiben unangetastet.
 */
class DemoPrintLayoutSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = User::query()->orderBy('id')->value('id');
        $hasDefault = ProjectPrintLayout::query()->where('is_default', true)->exists();
        $order = (int) ProjectPrintLayout::query()->max('order');
        $created = 0;

        foreach ($this->layouts() as $definition) {
            if (ProjectPrintLayout::query()->where('name', $definition['name'])->exists()) {
                continue;
            }

            $layout = ProjectPrintLayout::create([
                'name' => $definition['name'],
                'description' => $definition['description'],
                'is_default' => $definition['is_default'] && !$hasDefault,
                'columns_header' => $definition['columns_header'],
                'columns_body' => $definition['columns_body'],
                'columns_footer' => 1,
                'order' => ++$order,
                'is_active' => true,
                'user_id' => $ownerId,
                'permission' => 'allCanPrint',
                'notes' => ['header' => [], 'footer' => []],
            ]);
            $hasDefault = $hasDefault || $layout->is_default;
            $created++;

            foreach (['header', 'body', 'footer'] as $zone) {
                foreach ($definition[$zone] ?? [] as $rowIndex => $rowComponents) {
                    foreach ($rowComponents as $position => $componentRef) {
                        $component = $this->resolveComponent($componentRef);
                        if ($component === null) {
                            continue;
                        }
                        $layout->components()->create([
                            'component_id' => $component->id,
                            'type' => $zone,
                            'row' => $rowIndex + 1,
                            'position' => $position + 1,
                        ]);
                    }
                }
            }
        }

        Cache::forget('print_layout_components_not_special_v2');
        Cache::forget('print_layout_components_special_v2');
        Cache::forget('print_layout_all_components_v2');

        $this->command?->info(sprintf('Drucklayouts: %d neu angelegt.', $created));
    }

    /** Referenz = Enum (erste Komponente des Typs) oder [Enum, Name] für benannte Custom-Komponenten. */
    private function resolveComponent(ProjectTabComponentEnum|array $ref): ?Component
    {
        if (is_array($ref)) {
            [$type, $name] = $ref;

            return Component::query()->where('type', $type->value)->where('name', $name)->first();
        }

        return Component::query()->where('type', $ref->value)->orderBy('id')->first();
    }

    /** @return array<int, array<string, mixed>> */
    private function layouts(): array
    {
        $e = ProjectTabComponentEnum::class;

        return [
            [
                'name' => 'Projektsteckbrief',
                'description' => 'Kompakte Übersicht: Basisdaten, Status, Team und Zeitraum auf einer Seite.',
                'is_default' => false,
                'columns_header' => 2,
                'columns_body' => 2,
                'header' => [[$e::PROJECT_TITLE, $e::PROJECT_STATUS]],
                'body' => [
                    [$e::PROJECT_BASIC_DATA_DISPLAY, $e::PROJECT_ATTRIBUTES],
                    [$e::PROJECT_TEAM, $e::PROJECT_PERIOD],
                    [$e::GENERAL_SHIFT_INFORMATION, $e::PROJECT_COST_CENTER_DISPLAY],
                ],
            ],
            [
                'name' => 'Technische Vorlage',
                'description' => 'Für die Gewerke: technische Anforderungen, schichtrelevante Termine und Ansprechpersonen.',
                'is_default' => false,
                'columns_header' => 1,
                'columns_body' => 1,
                'header' => [[$e::PROJECT_TITLE]],
                'body' => [
                    [[$e::TEXT_AREA, 'Technische Anforderungen']],
                    [$e::RELEVANT_DATES_FOR_SHIFT_PLANNING],
                    [$e::SHIFT_CONTACT_PERSONS],
                    [$e::SHIFT_TAB],
                ],
            ],
            [
                'name' => 'Produktionsübersicht',
                'description' => 'Ausführliche Mappe: Status, Attribute, Budget-Deadline, Checklisten und Kommentare.',
                'is_default' => true,
                'columns_header' => 1,
                'columns_body' => 1,
                'header' => [[$e::PROJECT_TITLE]],
                'body' => [
                    [$e::PROJECT_STATUS],
                    [$e::PROJECT_ATTRIBUTES],
                    [$e::PROJECT_BUDGET_DEADLINE],
                    [$e::CHECKLIST_ALL],
                    [$e::COMMENT_ALL_TAB],
                ],
            ],
        ];
    }
}
