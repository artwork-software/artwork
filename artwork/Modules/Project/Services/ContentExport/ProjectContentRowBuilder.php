<?php

namespace Artwork\Modules\Project\Services\ContentExport;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Bereitet je Projekt die Werte der gewählten Spalten als reinen Text auf. Excel und Word nutzen dieselben
 * Texte; mehrere Einträge stehen je in einer eigenen Zeile.
 */
class ProjectContentRowBuilder
{
    private const SEPARATOR = ' · ';

    public function __construct(
        private readonly ProjectContentExportQuery $query,
    ) {
    }

    /**
     * @param EloquentCollection<int, Project> $projects
     * @param array<int, array{key: string, title: string, show_label: bool, component: Component|null}> $columns
     * @return array<int, array{
     *     project: Project,
     *     cells: array<int, array{title: string, show_label: bool, value: string}>
     * }>
     */
    public function build(
        EloquentCollection $projects,
        array $columns,
        ProjectContentExportConfiguration $configuration,
    ): array {
        if ($projects->isEmpty()) {
            return [];
        }

        $keys = array_column($columns, 'key');
        $this->loadProjectRelations($projects, $keys);

        $projectIds = $projects->modelKeys();
        // Termine nur laden, wenn Termine oder Räume ausgegeben werden; sortiert wird über ein Aggregat
        $needsEvents = array_intersect(
            [ProjectContentExportColumns::EVENTS, ProjectContentExportColumns::ROOMS],
            $keys
        ) !== [];
        $events = $needsEvents ? $this->eventsByProject($projectIds, $configuration) : collect();
        $firstEventStarts = $this->firstEventStarts($projectIds, $configuration);
        $values = $this->componentValues($projectIds, $columns);
        $periods = in_array(ProjectContentExportColumns::PROJECT_PERIOD, $keys, true)
            ? $this->projectPeriods($projectIds)
            : [];

        $rows = $projects->map(function (Project $project) use (
            $columns,
            $events,
            $values,
            $periods,
            $firstEventStarts
        ) {
            $columnEvents = $events->get($project->id, collect());

            $cells = array_map(fn (array $column): array => [
                'title' => $column['title'],
                'show_label' => $column['show_label'],
                'value' => $this->withoutControlCharacters(
                    $this->value($column, $project, $columnEvents, $values, $periods)
                ),
            ], $columns);

            return [
                'project' => $project,
                'cells' => $cells,
                'first_event' => $firstEventStarts[$project->id] ?? PHP_INT_MAX,
            ];
        })->all();

        // Chronologisch nach dem ersten Termin (im Zeitraum), Projekte ohne Termin ans Ende, dann nach Name
        usort($rows, static function (array $left, array $right): int {
            $leftTime = $left['first_event'];
            $rightTime = $right['first_event'];

            return [$leftTime, mb_strtolower((string) $left['project']->name)] <=>
                [$rightTime, mb_strtolower((string) $right['project']->name)];
        });

        return array_map(static fn (array $row): array => [
            'project' => $row['project'],
            'cells' => $row['cells'],
        ], $rows);
    }

    /**
     * @param array{key: string, component: Component|null} $column
     * @param Collection<int, Event> $events
     * @param Collection<string, ProjectComponentValue> $values
     * @param array<int, string> $periods
     */
    private function value(
        array $column,
        Project $project,
        Collection $events,
        Collection $values,
        array $periods
    ): string {
        $component = $column['component'];
        if ($component !== null) {
            $stored = $values->get($component->id . ':' . $project->id);

            // Wie im Projekt-Tab: ohne eigenen Wert gilt der Standardwert der Komponente
            return $this->componentValue($component, $stored?->data ?? $component->data ?? []);
        }

        return match ($column['key']) {
            ProjectContentExportColumns::PROJECT_NAME => (string) $project->name,
            ProjectContentExportColumns::ARTISTS => $project->artistDisplayText(),
            ProjectContentExportColumns::EVENTS => $events->map(fn (Event $event) => $this->eventLine($event))
                ->implode("\n"),
            ProjectContentExportColumns::ROOMS => $events->map(fn (Event $event) => $event->room?->name)
                ->filter()
                ->unique()
                ->implode("\n"),
            ProjectContentExportColumns::PROJECT_STATE => (string) ($project->getRelation('status')?->name ?? ''),
            ProjectContentExportColumns::PROJECT_GROUP => $project->groups->pluck('name')->implode("\n"),
            ProjectContentExportColumns::PROJECT_PERIOD => $periods[$project->id] ?? '',
            ProjectContentExportColumns::PROJECT_PROPERTIES => $project->categories
                ->concat($project->genres)
                ->concat($project->sectors)
                ->pluck('name')
                ->implode(', '),
            default => '',
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    public function componentValue(Component $component, array $data): string
    {
        return match (ProjectTabComponentEnum::tryFrom((string) $component->type)) {
            ProjectTabComponentEnum::TEXT_FIELD,
            ProjectTabComponentEnum::TEXT_AREA,
            ProjectTabComponentEnum::LINK => $this->normalizeLineBreaks((string) (is_scalar($data['text'] ?? null)
                ? $data['text']
                : '')),
            ProjectTabComponentEnum::CHECKBOX => filter_var($data['checked'] ?? false, FILTER_VALIDATE_BOOLEAN)
                ? __('Yes')
                : __('No'),
            ProjectTabComponentEnum::DROPDOWN,
            ProjectTabComponentEnum::CHECKBOX_LIST => implode("\n", $this->selectedValues($data['selected'] ?? null)),
            ProjectTabComponentEnum::LINK_LIST => collect(is_array($data['links'] ?? null) ? $data['links'] : [])
                ->filter(fn (mixed $link): bool => is_array($link))
                ->map(function (array $link): string {
                    $label = trim((string) ($link['label'] ?? ''));
                    $url = trim((string) ($link['url'] ?? ''));
                    if ($label !== '' && $url !== '' && $label !== $url) {
                        return $label . ': ' . $url;
                    }

                    return $url !== '' ? $url : $label;
                })
                ->filter(fn (string $line): bool => $line !== '')
                ->implode("\n"),
            default => '',
        };
    }

    /**
     * Auswahl liegt je nach Alter und Einstellung als Text, Objekt {value} oder Liste davon vor.
     *
     * @return array<int, string>
     */
    private function selectedValues(mixed $selected): array
    {
        if ($selected === null || $selected === '') {
            return [];
        }
        if (is_array($selected) && array_key_exists('value', $selected)) {
            $selected = [$selected['value']];
        }

        return collect(is_array($selected) ? $selected : [$selected])
            ->map(fn (mixed $entry): mixed => is_array($entry) ? ($entry['value'] ?? null) : $entry)
            ->filter(fn (mixed $entry): bool => is_scalar($entry) && trim((string) $entry) !== '')
            ->map(fn (mixed $entry): string => trim((string) $entry))
            ->values()
            ->all();
    }

    private function eventLine(Event $event): string
    {
        $start = $this->localized($event->start_time);
        $end = $this->localized($event->end_time);
        $sameDay = $start->isSameDay($end);

        if ($event->allDay) {
            $date = $sameDay
                ? $start->translatedFormat('D, d.m.Y')
                : $start->translatedFormat('D, d.m.Y') . ' – ' . $end->translatedFormat('D, d.m.Y');
            $when = $date . self::SEPARATOR . __('All day');
        } elseif ($sameDay) {
            $when = $start->translatedFormat('D, d.m.Y') . self::SEPARATOR . $start->format('H:i') . '–' .
                $end->format('H:i');
        } else {
            $when = $start->translatedFormat('D, d.m.Y H:i') . ' – ' . $end->translatedFormat('D, d.m.Y H:i');
        }

        return collect([$event->event_type?->name, $when, $event->room?->name])
            ->filter(fn (?string $part): bool => $part !== null && $part !== '')
            ->implode(self::SEPARATOR);
    }

    private function localized(CarbonInterface $date): CarbonInterface
    {
        return $date->copy()->locale(app()->getLocale());
    }

    /**
     * Steuerzeichen (außer Zeilenumbruch und Tab) sind in Excel- und Word-XML ungültig und machen die Datei kaputt.
     */
    private function withoutControlCharacters(string $text): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
    }

    private function normalizeLineBreaks(string $text): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $text));
    }

    /**
     * @param EloquentCollection<int, Project> $projects
     * @param array<int, string> $keys
     */
    private function loadProjectRelations(EloquentCollection $projects, array $keys): void
    {
        $relations = [];
        if (in_array(ProjectContentExportColumns::PROJECT_STATE, $keys, true)) {
            $relations[] = 'status';
        }
        if (in_array(ProjectContentExportColumns::PROJECT_GROUP, $keys, true)) {
            $relations['groups'] = fn ($query) => $query->without('users')->select(['projects.id', 'projects.name']);
        }
        if (in_array(ProjectContentExportColumns::PROJECT_PROPERTIES, $keys, true)) {
            $relations = [...$relations, 'categories', 'genres', 'sectors'];
        }
        if ($relations !== []) {
            $projects->load($relations);
        }
    }

    /**
     * Termine der Projekte (bei gesetztem Zeitraum nur darin) chronologisch, für Sortierung, Termine und Räume.
     *
     * @param array<int, int> $projectIds
     * @return Collection<int, Collection<int, Event>>
     */
    private function eventsByProject(array $projectIds, ProjectContentExportConfiguration $configuration): Collection
    {
        $query = Event::query()
            ->whereIn('events.project_id', $projectIds)
            ->whereNotNull('events.start_time')
            ->whereNotNull('events.end_time')
            // Terminarten der Termin-Spalte gelten auch für die Räume-Spalte
            ->when(
                $configuration->eventColumnTypeIds !== [],
                fn ($events) => $events->whereIn('events.event_type_id', $configuration->eventColumnTypeIds)
            )
            ->with([
                'event_type:id,name',
                // Raum ohne seine Standard-Relationen (Raum-Admins, Ersteller)
                'room' => fn ($rooms) => $rooms->without(['admins', 'creator'])->select(['id', 'name']),
            ])
            ->orderBy('events.start_time')
            ->orderBy('events.id');

        return $this->query->overlapping($query, $configuration)
            ->get(['events.id', 'events.project_id', 'events.event_type_id', 'events.room_id', 'events.start_time',
                'events.end_time', 'events.allDay'])
            ->groupBy('project_id');
    }

    /**
     * Beginn des ersten Termins je Projekt (bei gesetztem Zeitraum im Zeitraum) als Zeitstempel, für die Sortierung.
     *
     * @param array<int, int> $projectIds
     * @return array<int, int>
     */
    private function firstEventStarts(array $projectIds, ProjectContentExportConfiguration $configuration): array
    {
        $query = Event::query()
            ->whereIn('events.project_id', $projectIds)
            ->whereNotNull('events.start_time')
            ->whereNotNull('events.end_time')
            ->groupBy('events.project_id')
            ->selectRaw('events.project_id, MIN(events.start_time) as first_start');

        return $this->query->overlapping($query, $configuration)
            ->toBase()
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (int) $row->project_id => Carbon::parse($row->first_start)->getTimestamp(),
            ])
            ->all();
    }

    /**
     * @param array<int, int> $projectIds
     * @param array<int, array{component: Component|null}> $columns
     * @return Collection<string, ProjectComponentValue>
     */
    private function componentValues(array $projectIds, array $columns): Collection
    {
        $componentIds = collect($columns)->pluck('component.id')->filter()->unique()->values()->all();
        if ($componentIds === []) {
            return collect();
        }

        return ProjectComponentValue::query()
            ->whereIn('project_id', $projectIds)
            ->whereIn('component_id', $componentIds)
            ->get(['id', 'project_id', 'component_id', 'data'])
            ->keyBy(fn (ProjectComponentValue $value): string => $value->component_id . ':' . $value->project_id);
    }

    /**
     * Projektzeitraum wie in der Projektübersicht: Termine der für den Zeitraum relevanten Terminarten, ohne
     * solche alle Termine des Projekts.
     *
     * @param array<int, int> $projectIds
     * @return array<int, string>
     */
    private function projectPeriods(array $projectIds): array
    {
        $aggregate = fn (bool $onlyRelevant) => Event::query()
            ->when($onlyRelevant, fn ($query) => $query
                ->join('event_types', 'events.event_type_id', '=', 'event_types.id')
                ->where('event_types.relevant_for_project_period', true))
            ->whereIn('events.project_id', $projectIds)
            ->groupBy('events.project_id')
            ->selectRaw('events.project_id, MIN(events.start_time) as first_start, MAX(events.end_time) as last_end')
            ->get()
            ->keyBy('project_id');

        $relevant = $aggregate(true);
        $fallback = $aggregate(false);

        $periods = [];
        foreach ($projectIds as $projectId) {
            $period = $relevant->get($projectId) ?? $fallback->get($projectId);
            $firstStart = $period?->getAttribute('first_start');
            $lastEnd = $period?->getAttribute('last_end');
            if ($firstStart === null || $lastEnd === null) {
                continue;
            }
            $periods[$projectId] = Carbon::parse($firstStart)->format('d.m.Y') . ' – ' .
                Carbon::parse($lastEnd)->format('d.m.Y');
        }

        return $periods;
    }
}
