<?php

namespace Artwork\Modules\Project\Services\ContentExport;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ProjectCreateSettings;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Spalten des Projektinhalte-Exports: Systembausteine und eigene Komponenten (Text, Checkbox, Dropdown,
 * Link, Linkliste). Spalten werden über Schlüssel angesprochen ("system:events", "component:12"), damit
 * Vorlagen beide Arten in einer frei sortierbaren Liste speichern.
 */
class ProjectContentExportColumns
{
    public const COMPONENT_PREFIX = 'component:';

    public const PROJECT_NAME = 'system:project_name';
    public const ARTISTS = 'system:artists';
    public const EVENTS = 'system:events';
    public const ROOMS = 'system:rooms';
    public const PROJECT_STATE = 'system:project_state';
    public const PROJECT_GROUP = 'system:project_group';
    public const PROJECT_PERIOD = 'system:project_period';
    public const PROJECT_PROPERTIES = 'system:project_properties';

    /**
     * Systembausteine mit ihrem Übersetzungsschlüssel, in Standard-Reihenfolge.
     */
    private const SYSTEM_COLUMNS = [
        self::PROJECT_NAME => 'Project name',
        self::ARTISTS => 'Artists',
        self::EVENTS => 'Events',
        self::ROOMS => 'Rooms',
        self::PROJECT_STATE => 'Project status',
        self::PROJECT_GROUP => 'Project group',
        self::PROJECT_PERIOD => 'Project period',
        self::PROJECT_PROPERTIES => 'Project properties',
    ];

    public const COMPONENT_TYPES = [
        ProjectTabComponentEnum::TEXT_FIELD,
        ProjectTabComponentEnum::TEXT_AREA,
        ProjectTabComponentEnum::CHECKBOX,
        ProjectTabComponentEnum::CHECKBOX_LIST,
        ProjectTabComponentEnum::DROPDOWN,
        ProjectTabComponentEnum::LINK,
        ProjectTabComponentEnum::LINK_LIST,
    ];

    public function __construct(
        private readonly ProjectComponentVisibilityService $visibilityService,
        private readonly ProjectCreateSettings $projectCreateSettings,
    ) {
    }

    /**
     * @return array<string, string> Schlüssel => Übersetzungsschlüssel
     */
    public function systemColumns(): array
    {
        $columns = self::SYSTEM_COLUMNS;
        if (!$this->projectCreateSettings->show_artists) {
            unset($columns[self::ARTISTS]);
        }

        return $columns;
    }

    /**
     * Eigene Komponenten der exportierbaren Typen, die die Person sehen darf (Komponenten-Berechtigung und
     * Tab-Sichtbarkeit, wie Projektübersicht und Drucklayout).
     *
     * @return EloquentCollection<int, Component>
     */
    public function visibleComponents(User $user): EloquentCollection
    {
        $components = Component::query()
            ->notSpecial()
            ->whereIn('type', array_map(
                static fn (ProjectTabComponentEnum $type): string => $type->value,
                self::COMPONENT_TYPES
            ))
            ->orderBy('name')
            ->get();

        $visibleIds = $this->visibilityService->visibleInProjectComponentIds($user, $components)->flip();

        return $components->filter(fn (Component $component): bool => isset($visibleIds[$component->id]))->values();
    }

    /**
     * Komponenten tragen die Namen ihrer Tabs mit, damit gleichnamige Komponenten (z. B. aus einem kopierten Tab)
     * unterscheidbar sind.
     *
     * @return array<int, array{key: string, name: string, kind: string, type: string, tabs: array<int, string>}>
     */
    public function availableColumns(User $user): array
    {
        $system = [];
        foreach ($this->systemColumns() as $key => $translationKey) {
            $system[] = [
                'key' => $key,
                'name' => __($translationKey),
                'kind' => 'system',
                'type' => $key,
                'tabs' => [],
            ];
        }

        $visibleComponents = $this->visibleComponents($user);
        $tabIdsByComponent = $this->visibilityService->visibleTabIdsByComponent(
            $user,
            collect($visibleComponents->modelKeys())
        );
        $tabLabels = $this->tabLabels();

        $components = $visibleComponents->map(fn (Component $component): array => [
            'key' => self::COMPONENT_PREFIX . $component->id,
            'name' => (string) $component->name,
            'kind' => 'component',
            'type' => (string) $component->type,
            'tabs' => collect($tabIdsByComponent[$component->id] ?? [])
                ->sortBy(fn (int $tabId): int => $tabLabels[$tabId]['position'] ?? PHP_INT_MAX)
                ->map(fn (int $tabId): ?string => $tabLabels[$tabId]['label'] ?? null)
                ->filter()
                ->values()
                ->all(),
        ])->all();

        return [...$system, ...$components];
    }

    /**
     * Anzeigename je Tab in Tab-Reihenfolge; gleichnamige Tabs werden durchnummeriert („Abfrage (2)“).
     *
     * @return array<int, array{label: string, position: int}>
     */
    private function tabLabels(): array
    {
        $tabs = ProjectTab::query()
            ->without(['components', 'sidebarTabs'])
            ->orderBy('order')
            ->orderBy('id')
            ->get(['id', 'name']);
        $totals = $tabs->countBy(static fn (ProjectTab $tab): string => mb_strtolower(trim((string) $tab->name)));

        $labels = [];
        $seen = [];
        foreach ($tabs->values() as $position => $tab) {
            $normalized = mb_strtolower(trim((string) $tab->name));
            $seen[$normalized] = ($seen[$normalized] ?? 0) + 1;
            $labels[$tab->id] = [
                'label' => $totals[$normalized] > 1
                    ? sprintf('%s (%d)', $tab->name, $seen[$normalized])
                    : (string) $tab->name,
                'position' => $position,
            ];
        }

        return $labels;
    }

    public static function componentIdOf(string $key): ?int
    {
        if (!str_starts_with($key, self::COMPONENT_PREFIX)) {
            return null;
        }
        $id = substr($key, strlen(self::COMPONENT_PREFIX));

        return ctype_digit($id) ? (int) $id : null;
    }

    public static function isSystemKey(string $key): bool
    {
        return array_key_exists($key, self::SYSTEM_COLUMNS);
    }

    /**
     * Prüft die Form eines Spaltenschlüssels (ob die Komponente existiert und sichtbar ist, entscheidet der Export).
     */
    public static function isValidKey(string $key): bool
    {
        return self::isSystemKey($key) || self::componentIdOf($key) !== null;
    }

    public function defaultTitle(string $key, ?Component $component): string
    {
        if ($component !== null) {
            return (string) $component->name;
        }

        return __(self::SYSTEM_COLUMNS[$key] ?? $key);
    }
}
