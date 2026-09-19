<?php

namespace Artwork\Modules\Project\TabTemplates;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\ProjectTab;
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
            'prerequisites' => __($template['prerequisites']),
            'component_count' => count($template['components']),
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
                    // Dokument-/Sammel-Komponenten zeigen die Inhalte DIESES Tabs
                    'scope' => isset($definition['special']) ? [$tab->id] : [],
                    'note' => isset($definition['note']) && $definition['note'] !== '' ? __($definition['note']) : null,
                ]);
            }

            return $tab;
        });
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function createComponent(array $definition): Component
    {
        $type = ProjectTabComponentEnum::from($definition['type']);
        $data = $definition['data'];

        // Beschriftungen, Platzhalter und Optionen in der Sprache der anlegenden Person speichern
        foreach (['label', 'placeholder', 'title'] as $textKey) {
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

        /** @var Component $component */
        $component = Component::query()->create([
            'name' => __($definition['name']),
            'type' => $type->value,
            'data' => $data,
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => 'allSeeAndEdit',
        ]);

        return $component;
    }

    private function resolveSpecialComponent(string $type): ?Component
    {
        /** @var Component|null $component */
        $component = Component::query()
            ->where('type', $type)
            ->where('special', true)
            ->orderBy('id')
            ->first();

        return $component;
    }

    private static function specialLabel(string $type): string
    {
        return match ($type) {
            ProjectTabComponentEnum::PROJECT_DOCUMENTS->value => 'Documents',
            default => $type,
        };
    }
}
