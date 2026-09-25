<?php

namespace Artwork\Modules\Project\TabTemplates;

use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\ProjectTabSidebarTab;
use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;

/**
 * Legt aus einer Vorlage einen ganz normalen Tab mit ganz normalen Komponenten an. Nach dem Anlegen
 * ist nichts mehr „Vorlage“: Tab und Komponenten sind wie von Hand erstellt (umbenennen, verschieben,
 * löschen, Sichtbarkeit) — es gibt keinen Zwischenzustand.
 */
class ProjectTabTemplateService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {
    }

    /**
     * @return list<array<string, mixed>> Vorlagen für die UI (übersetzt, ohne Rohdaten der Komponenten)
     */
    public function listForUi(): array
    {
        return array_values(array_map(static fn (array $template): array => [
            'key' => $template['key'],
            'name' => __($template['name']),
            'description' => __($template['description']),
            'prerequisites' => ($template['prerequisites'] ?? '') !== '' ? __($template['prerequisites']) : '',
            'component_count' => count($template['components']),
            'sidebar_tabs' => array_map(
                static fn (array $sidebarTab): string => __($sidebarTab['name']),
                $template['sidebar'] ?? [],
            ),
            'components' => array_map(static fn (array $component): array => [
                'type' => $component['type'] ?? $component['special'],
                'name' => isset($component['special'])
                    ? __(self::specialLabel($component['special']))
                    : __($component['name']),
            ], $template['components']),
        ], ProjectTabTemplateCatalog::all()));
    }

    public function apply(string $key): ProjectTab
    {
        $template = ProjectTabTemplateCatalog::find($key);
        if ($template === null) {
            throw new InvalidArgumentException("Unknown tab template {$key}");
        }

        return $this->db->transaction(function () use ($template): ProjectTab {
            $lastOrder = (int) (ProjectTab::query()->max('order') ?? 0);

            /** @var ProjectTab $tab */
            $tab = ProjectTab::query()->create([
                'name' => __($template['name']),
                'order' => $lastOrder + 1,
                'visible_for_all' => true,
            ]);

            $order = 0;
            foreach ($template['components'] as $definition) {
                $component = isset($definition['special'])
                    ? $this->resolveSpecialComponent($definition['special'])
                    : $this->createComponent($definition);

                if ($component === null) {
                    continue; // System-Komponente in dieser Instanz nicht vorhanden → überspringen
                }

                ComponentInTab::query()->create([
                    'project_tab_id' => $tab->id,
                    'component_id' => $component->id,
                    'order' => $order++,
                    // Dokument-/Sammel-Komponenten zeigen die Inhalte DIESES Tabs (Werkzeuge: kein Tab-Bezug)
                    'scope' => isset($definition['special']) && ($definition['scope'] ?? 'tab') === 'tab'
                        ? [$tab->id]
                        : [],
                    'note' => isset($definition['note']) && $definition['note'] !== '' ? __($definition['note']) : null,
                ]);
            }

            $this->createSidebarTabs($tab, $template['sidebar'] ?? []);

            return $tab;
        });
    }

    /**
     * @param array<string, mixed> $definition
     */
    public function createComponent(array $definition): Component
    {
        $type = ProjectTabComponentEnum::from($definition['type']);

        /** @var Component $component */
        $component = Component::query()->create([
            'name' => __($definition['name']),
            'type' => $type->value,
            'data' => $this->buildComponentData($definition),
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => 'allSeeAndEdit',
        ]);

        return $component;
    }

    /**
     * Komponenten-Daten einer Vorlagen-Definition: Texte in der aktuellen Sprache, Kontakttypen per Slug
     * als IDs dieser Instanz (fehlende Typen fallen weg).
     *
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    public function buildComponentData(array $definition): array
    {
        $data = $definition['data'];

        if (array_key_exists('contact_type_slugs', $data)) {
            $data['contact_type_ids'] = CrmContactType::query()
                ->whereIn('slug', (array) $data['contact_type_slugs'])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
            unset($data['contact_type_slugs']);
        }

        // Beschriftungen, Platzhalter und Optionen in der Sprache der anlegenden Person speichern
        foreach (['label', 'placeholder', 'title', 'description', 'subtitle'] as $textKey) {
            if (isset($data[$textKey]) && $data[$textKey] !== '') {
                $data[$textKey] = __($data[$textKey]);
            }
        }
        if (isset($data['options']) && is_array($data['options'])) {
            $data['options'] = array_map(
                static fn (array $option): array => ['value' => __($option['value'])],
                $data['options'],
            );
        }

        return $data;
    }

    public function resolveSpecialComponent(string $type): ?Component
    {
        /** @var Component|null $component */
        $component = Component::query()
            ->where('type', $type)
            ->where('special', true)
            ->orderBy('id')
            ->first();

        return $component;
    }

    /**
     * @param array<int, array<string, mixed>> $sidebarTabs
     */
    private function createSidebarTabs(ProjectTab $tab, array $sidebarTabs): void
    {
        foreach (array_values($sidebarTabs) as $index => $sidebarTab) {
            /** @var ProjectTabSidebarTab $sidebar */
            $sidebar = $tab->sidebarTabs()->create([
                'name' => __($sidebarTab['name']),
                'order' => $index + 1,
            ]);

            $order = 1;
            foreach ($sidebarTab['components'] as $type) {
                $component = $type === ProjectTabComponentEnum::SEPARATOR->value
                    ? $this->sidebarSeparator()
                    : $this->resolveSpecialComponent($type);
                if ($component === null) {
                    continue;
                }
                $sidebar->componentsInSidebar()->create([
                    'component_id' => $component->id,
                    'order' => $order++,
                ]);
            }
        }
    }

    /**
     * Trennlinie für Seitenleisten: die vorhandene Standard-Trenn-Komponente, sonst neu angelegt.
     */
    private function sidebarSeparator(): Component
    {
        /** @var Component $separator */
        $separator = Component::query()
            ->where('type', ProjectTabComponentEnum::SEPARATOR->value)
            ->orderByRaw("name = 'Separator 10 Pixel' desc")
            ->orderBy('id')
            ->first()
            ?? Component::query()->create([
                'name' => 'Separator 10 Pixel',
                'type' => ProjectTabComponentEnum::SEPARATOR->value,
                'data' => ['height' => '10', 'showLine' => true],
                'special' => false,
                'sidebar_enabled' => true,
                'permission_type' => 'allSeeAndEdit',
            ]);

        return $separator;
    }

    private static function specialLabel(string $type): string
    {
        return match ($type) {
            ProjectTabComponentEnum::PROJECT_DOCUMENTS->value => 'Documents',
            ProjectTabComponentEnum::BULK_EDIT->value => 'Schedule',
            ProjectTabComponentEnum::CHECKLIST->value => 'ChecklistComponent',
            ProjectTabComponentEnum::SHIFT_TAB->value => 'ShiftTab',
            ProjectTabComponentEnum::BUDGET->value => 'BudgetTab',
            ProjectTabComponentEnum::COMMENT_TAB->value => 'CommentTab',
            default => $type,
        };
    }
}
