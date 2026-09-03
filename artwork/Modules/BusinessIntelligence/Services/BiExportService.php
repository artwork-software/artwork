<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Enums\BiEffortBucketEnum;
use Artwork\Modules\BusinessIntelligence\Exports\BiExportWorkbook;
use Artwork\Modules\BusinessIntelligence\Exports\BiInfoSheetExport;
use Artwork\Modules\BusinessIntelligence\Exports\BiProjectExport;
use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategory;
use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\BusinessIntelligence\Models\BiExportPreset;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\BusinessIntelligence\Models\BiProjectData;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BiExportService
{
    public function __construct(
        private readonly BiDerivedValuesService $biDerivedValuesService,
        private readonly BiProjectMetricsService $biProjectMetricsService,
        private readonly GeneralSettings $generalSettings
    ) {
    }

    /**
     * Auswahl-Optionen (Spalten, Tag-/Custom-Feld-Spalten, Presets) für die
     * Export-UIs — geteilt zwischen Settings-Exportseite und BI-Dashboard.
     *
     * @return array<string, mixed>
     */
    public function exportConfigurationOptions(?int $forUserId = null, bool $isAdmin = false): array
    {
        // Sage-basierte Spalten nur anbieten, wenn die Schnittstelle aktiv ist
        $sageEnabled = $this->biDerivedValuesService->isSageApiEnabled();
        $hidden = $sageEnabled ? [] : self::SAGE_COLUMNS;

        $columns = collect(self::columnLabelMap())
            ->except($hidden)
            ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])
            ->values();

        $tagColumns = BiEventTypeTag::query()
            ->get(['id', 'name', 'name_de'])
            ->map(fn (BiEventTypeTag $tag) => [
                'key' => 'tag_' . $tag->id,
                'label' => $tag->name_de ?: $tag->name,
            ]);

        $customFieldColumns = Component::isBiField()
            ->orderBy('bi_order')
            ->get(['id', 'name'])
            ->map(fn (Component $component) => [
                'key' => 'custom_field_' . $component->id,
                'label' => $component->name,
            ]);

        $audienceCategoryColumns = BiAudienceCategory::ordered()
            ->get(['id', 'name'])
            ->map(fn (BiAudienceCategory $category) => [
                'key' => 'audience_cat_' . $category->id,
                'label' => $category->name,
            ]);

        // Spaltenkatalog in Gruppen — EINE Quelle für Picker, Reihenfolge und Validierung
        $columnGroups = [];
        foreach (self::columnCatalog() as $groupKey => $group) {
            $groupColumns = [];
            foreach ($group['columns'] as $key => $label) {
                if (in_array($key, $hidden, true)) {
                    continue;
                }
                $groupColumns[] = ['key' => $key, 'label' => $label, 'translate' => true];
            }
            if ($groupKey === 'quotas') {
                foreach ($audienceCategoryColumns as $column) {
                    $groupColumns[] = [...$column, 'translate' => false];
                }
            }
            $columnGroups[] = [
                'key' => $groupKey,
                'label' => $group['label'],
                'default' => $group['default'],
                'columns' => $groupColumns,
            ];
        }
        $columnGroups[] = [
            'key' => 'tags',
            'label' => 'BI tags (event days per tag)',
            'default' => false,
            'columns' => $tagColumns->map(fn (array $column) => [...$column, 'translate' => false])->all(),
        ];
        $columnGroups[] = [
            'key' => 'custom',
            'label' => 'BI fields',
            'default' => false,
            'columns' => $customFieldColumns->map(fn (array $column) => [...$column, 'translate' => false])->all(),
        ];
        $columnGroups = array_values(array_filter($columnGroups, static fn (array $group) => count($group['columns']) > 0));

        $defaultColumns = [];
        foreach ($columnGroups as $group) {
            if ($group['default']) {
                foreach ($group['columns'] as $column) {
                    if (($column['translate'] ?? true) === true) {
                        $defaultColumns[] = $column['key'];
                    }
                }
            }
        }

        $presets = BiExportPreset::query()
            ->orderBy('name')
            ->get(['id', 'name', 'columns', 'is_shared', 'created_by'])
            ->map(fn (BiExportPreset $preset) => [
                'id' => $preset->id,
                'name' => $preset->name,
                'columns' => $preset->columns,
                'is_shared' => $preset->is_shared,
                'created_by' => $preset->created_by,
                // Löschen/Umbenennen nur durch Ersteller*in oder Admin
                'can_manage' => $isAdmin || ($forUserId !== null && $preset->created_by === $forUserId),
            ])
            ->values();

        return [
            'columns' => $columns,
            'tagColumns' => $tagColumns,
            'customFieldColumns' => $customFieldColumns,
            'audienceCategoryColumns' => $audienceCategoryColumns,
            'columnGroups' => $columnGroups,
            'defaultColumns' => $defaultColumns,
            'presets' => $presets,
        ];
    }

    /** Spalten, die nur mit aktiver Sage-Schnittstelle angeboten werden. */
    public const SAGE_COLUMNS = ['booking_count', 'bookings_per_performance'];

    /**
     * Spaltenkatalog: Gruppen in Anzeige- UND Exportreihenfolge. Werte sind
     * Übersetzungsschlüssel. project_id steht immer vorn (Schlüssel für Pivots).
     *
     * @return array<string, array{label: string, default: bool, columns: array<string, string>}>
     */
    public static function columnCatalog(): array
    {
        return [
            'identity' => [
                'label' => 'Master data',
                'default' => true,
                'columns' => [
                    'project_id' => 'Project ID',
                    'project_name' => 'Project name',
                    'project_state' => 'Project status',
                    'artists' => 'Artist / Group',
                    'cost_center' => 'Cost bearer',
                    'main_category' => 'Category (Sector)',
                    'rooms' => 'Room',
                    'areas' => 'Area',
                    'first_event_date' => 'First performance',
                    'season_year' => 'Year',
                ],
            ],
            'audience' => [
                'label' => 'Audience & revenue',
                'default' => true,
                'columns' => [
                    'visitors' => 'Visitors',
                    'sold_tickets' => 'Sold tickets',
                    'revenue' => 'Revenue',
                    'avg_price' => 'Average price',
                    'seats_capacity' => 'Number of seats',
                    'occupancy_rate' => 'Occupancy rate',
                ],
            ],
            'quotas' => [
                'label' => 'Audience categories & quotas',
                'default' => true,
                'columns' => [
                    'tickets_issued' => 'Tickets issued',
                    'free_tickets_rate' => 'Free ticket rate',
                    'reduced_tickets_rate' => 'Reduced ticket rate',
                    'paying_rate' => 'Paying rate',
                    'no_show_rate' => 'No-show rate',
                    'seat_occupancy' => 'Seat occupancy (incl. free tickets)',
                ],
            ],
            'production' => [
                'label' => 'Production data',
                'default' => true,
                'columns' => [
                    'production_type' => 'Production type',
                    'is_new_production' => 'New production',
                    'is_co_production' => 'Co-production',
                    'is_own_production' => 'Own production',
                    'is_germany_premiere' => 'Germany premiere',
                    'premiere_date' => 'Premiere date',
                ],
            ],
            'effort' => [
                'label' => 'Effort & steering',
                'default' => true,
                'columns' => [
                    'contract_count' => 'Contracts',
                    'event_count' => 'Events',
                    'booking_count' => 'Bookings',
                    'task_total' => 'Tasks total',
                    'task_open' => 'Tasks open',
                    'task_done' => 'Tasks done',
                    'document_count' => 'Documents',
                    'department_count' => 'Departments involved',
                    'user_count' => 'People involved',
                    'time_efforts' => 'Time efforts',
                    'effort_score' => 'Effort score',
                    'contracts_per_performance' => 'Contracts / performance',
                    'bookings_per_performance' => 'Bookings / performance',
                    'tasks_docs_per_production' => 'Tasks + documents',
                ],
            ],
            'plan' => [
                'label' => 'Plan',
                'default' => true,
                'columns' => [
                    'plan_visitors' => 'Plan visitors',
                    'plan_sold_tickets' => 'Plan sold tickets',
                    'plan_revenue' => 'Plan revenue',
                    'attainment' => 'Attainment',
                ],
            ],
        ];
    }

    /**
     * Zulässige Spaltenschlüssel (statisch + dynamisch), für die Request-Validierung.
     *
     * @return array<int, string>
     */
    public static function allowedColumnKeys(): array
    {
        $keys = array_keys(self::columnLabelMap());
        foreach (BiEventTypeTag::query()->pluck('id') as $id) {
            $keys[] = 'tag_' . $id;
        }
        foreach (Component::isBiField()->pluck('id') as $id) {
            $keys[] = 'custom_field_' . $id;
        }
        foreach (BiAudienceCategory::withTrashed()->pluck('id') as $id) {
            $keys[] = 'audience_cat_' . $id;
        }

        return $keys;
    }

    /**
     * Spalten in Katalogreihenfolge (statische Spalten vorn, dann Kategorien, Tags,
     * BI-Felder) — unabhängig von der Klick-Reihenfolge im Picker. project_id ist
     * immer die erste Spalte, damit sich Blätter in Excel verknüpfen lassen.
     *
     * @param array<int, string> $columns
     * @return array<int, string>
     */
    public static function orderColumns(array $columns): array
    {
        $rank = array_flip(array_keys(self::columnLabelMap()));
        $prefixRank = ['audience_cat_' => 1, 'tag_' => 2, 'custom_field_' => 3];
        $selected = array_values(array_unique(array_filter($columns, 'is_string')));

        usort($selected, static function (string $a, string $b) use ($rank, $prefixRank): int {
            $score = static function (string $key) use ($rank, $prefixRank): array {
                if (isset($rank[$key])) {
                    return [0, $rank[$key], 0];
                }
                foreach ($prefixRank as $prefix => $group) {
                    if (str_starts_with($key, $prefix)) {
                        return [$group, (int) substr($key, strlen($prefix)), 0];
                    }
                }

                return [9, 0, 0];
            };

            return $score($a) <=> $score($b);
        });

        return array_values(array_unique(['project_id', ...$selected]));
    }

    public function cacheExportConfiguration(array $config): string
    {
        $token = Str::uuid()->toString();
        Cache::put('bi_export_' . $token, $config, now()->addMinutes(30));
        Cache::put('bi_export_status_' . $token, ['status' => 'pending'], now()->addMinutes(60));

        return $token;
    }

    /**
     * Build the export file for a cached configuration and store it on disk.
     * Runs inside the queued job; writes a status flag the frontend polls.
     */
    public function generateAndStore(string $token): void
    {
        $config = Cache::get('bi_export_' . $token);

        if (!$config) {
            Cache::put('bi_export_status_' . $token, ['status' => 'failed'], now()->addMinutes(30));

            return;
        }

        try {
            $export = $this->buildExport($config);
            $export->store($this->storagePath($token), 'local');

            Cache::put(
                'bi_export_status_' . $token,
                ['status' => 'ready', 'filename' => $this->buildFilename($config)],
                now()->addHours(24)
            );
        } catch (\Throwable $exception) {
            Cache::put(
                'bi_export_status_' . $token,
                ['status' => 'failed', 'message' => $exception->getMessage()],
                now()->addMinutes(30)
            );

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(string $token): array
    {
        $status = Cache::get('bi_export_status_' . $token) ?? ['status' => 'unknown'];

        // Datei vom Aufräum-Job entfernt → "abgelaufen" statt eines scheinbar fertigen Exports
        if (($status['status'] ?? null) === 'ready' && !Storage::disk('local')->exists($this->storagePath($token))) {
            return ['status' => 'expired'];
        }

        return $status;
    }

    /**
     * Datei bleibt bis zum Aufräum-Job (artwork:bi-exports:cleanup) liegen, damit
     * ein zweiter Download oder ein Browser-Retry nicht ins Leere läuft.
     */
    public function downloadStored(string $token): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $path = $this->storagePath($token);

        if (!Storage::disk('local')->exists($path)) {
            return redirect()->route('bi.dashboard', ['export' => 'expired']);
        }

        $status = Cache::get('bi_export_status_' . $token) ?? [];
        $filename = $status['filename'] ?? ('bi-export-' . now()->format('Y-m-d_His') . '.xlsx');

        return response()->download(Storage::disk('local')->path($path), $filename);
    }

    /**
     * Sprechender Dateiname: Projekt (bei Einzel-Export) bzw. Anzahl, Zeitraum, Zeitstempel.
     *
     * @param array<string, mixed> $config
     */
    private function buildFilename(array $config): string
    {
        [$from, $to] = $this->resolveDateRange($config);
        $projectIds = $config['project_ids'] ?? [];

        $subject = count($projectIds) === 1
            ? Str::slug((string) (Project::query()->whereKey($projectIds[0])->value('name') ?? 'projekt'))
            : count($projectIds) . '-' . Str::slug(__('Productions'));

        $period = ($from || $to)
            ? '_' . ($from?->format('Y-m-d') ?? '') . '_bis_' . ($to?->format('Y-m-d') ?? '')
            : '';

        return 'bi-export_' . $subject . $period . '_' . now()->format('Y-m-d_Hi') . '.xlsx';
    }

    /**
     * Alle abgelegten Exportdateien, die älter als $maxAgeHours sind, löschen.
     *
     * @return int Anzahl gelöschter Dateien
     */
    public function cleanupStoredExports(int $maxAgeHours = 24): int
    {
        $disk = Storage::disk('local');
        $threshold = now()->subHours($maxAgeHours)->getTimestamp();
        $deleted = 0;

        foreach ($disk->files('bi-exports') as $file) {
            if ($disk->lastModified($file) < $threshold) {
                $disk->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    private function storagePath(string $token): string
    {
        return 'bi-exports/' . $token . '.xlsx';
    }

    private function buildExport(array $config): BiExportWorkbook
    {
        [$from, $to] = $this->resolveDateRange($config);

        $granularity = $config['granularity'] ?? 'both';
        // Katalogreihenfolge statt Klick-Reihenfolge; project_id immer vorn
        $columns = count($config['columns'] ?? []) > 0 ? self::orderColumns($config['columns']) : [];
        $tagFilter = $config['event_tag_filter'] ?? [];

        $projects = Project::whereIn('id', $config['project_ids'])
            ->with([
                'biData',
                'biEventData.event',
                'biRoomCapacities',
                'biAudienceCategoryValues',
                'planBiData',
                'planBiEventData.event',
                'biTimeEfforts.user',
                'categories',
                'contracts',
                'costCenter',
                'events.room.area',
                'events.event_type.biTags',
                'checklists.tasks',
                'departments',
                'users',
                'status',
            ])
            ->get();

        $sheets = [];

        if ($granularity !== 'events' && count($columns) > 0) {
            $rows = [];
            foreach ($projects as $project) {
                $rows[] = $this->buildProjectRow($project, $columns, $from, $to);
            }

            $sheets[] = new BiProjectExport(
                $rows,
                $columns,
                $this->buildColumnLabels($columns),
                __('Productions')
            );
        }

        if ($granularity !== 'projects') {
            // Kategorien-Spalten hängen dynamisch hinter dem festen Spaltensatz
            $audienceCategories = BiAudienceCategory::ordered()->get(['id', 'name']);

            $eventLabels = array_map(
                static fn (string $label): string => __($label),
                self::eventColumnLabelMap()
            );
            foreach ($audienceCategories as $category) {
                $eventLabels['audience_cat_' . $category->id] = $category->name;
            }
            $eventColumns = array_keys($eventLabels);

            $eventRows = [];
            foreach ($projects as $project) {
                foreach ($this->buildEventRows($project, $from, $to, $tagFilter, $audienceCategories) as $eventRow) {
                    $eventRows[] = $eventRow;
                }
            }

            $sheets[] = new BiProjectExport($eventRows, $eventColumns, $eventLabels, __('Events'));
        }

        $sheets[] = new BiInfoSheetExport($this->buildInfoEntries($config, $projects, $from, $to), __('Info'));

        return new BiExportWorkbook($sheets);
    }

    /**
     * Herkunft der Datei: Zeitraum (inkl. Quelle), Struktur, Filter, Ersteller, Zeitstempel.
     *
     * @param array<string, mixed> $config
     * @param \Illuminate\Support\Collection<int, Project> $projects
     * @return array<int, array{0: string, 1: mixed}>
     */
    private function buildInfoEntries(array $config, $projects, ?Carbon $from, ?Carbon $to): array
    {
        $explicitRange = !empty($config['date_from']) || !empty($config['date_to']);
        $periodSource = match (true) {
            $explicitRange => __('Chosen in the export dialog'),
            $from !== null || $to !== null => __('Season window from the tool settings'),
            default => __('No period — all events count'),
        };
        $period = ($from || $to)
            ? ($from?->format('d.m.Y') ?? '…') . ' – ' . ($to?->format('d.m.Y') ?? '…')
            : __('All periods');

        $granularityLabels = [
            'both' => 'Projects and events (2 sheets)',
            'projects' => 'Project rows only',
            'events' => 'Event rows only',
        ];
        $granularity = $config['granularity'] ?? 'both';

        $tagFilter = $config['event_tag_filter'] ?? [];
        $tagNames = BiEventTypeTag::query()
            ->whereIn('id', array_map('intval', array_filter($tagFilter, 'is_numeric')))
            ->get()
            ->map(fn (BiEventTypeTag $tag) => $tag->name_de ?: $tag->name)
            ->all();
        if (in_array('untagged', $tagFilter, true)) {
            $tagNames[] = __('Events without BI tag');
        }

        $projectNames = $projects->pluck('name')->sort()->values();
        $listed = $projectNames->take(50)->implode(', ');
        if ($projectNames->count() > 50) {
            $listed .= ' … (+' . ($projectNames->count() - 50) . ')';
        }

        return [
            [__('Created at'), now()->format('d.m.Y H:i')],
            [__('Created by'), (string) ($config['user_name'] ?? '')],
            [__('Period'), $period],
            [__('Period source'), $periodSource],
            [__('Export structure'), __($granularityLabels[$granularity] ?? $granularity)],
            [__('Filter events by BI tags'), count($tagNames) > 0 ? implode(', ', $tagNames) : __('All events')],
            [__('Productions'), $projects->count()],
            [__('Selected productions'), $listed],
            [__('Note'), __('The column selection applies to the productions sheet; the events sheet always has the same columns.')],
        ];
    }

    /**
     * Fixed column set of the per-event sheet. Values are translation keys.
     *
     * @return array<string, string>
     */
    public static function eventColumnLabelMap(): array
    {
        return [
            'event_id' => 'Event ID',
            'project_id' => 'Project ID',
            'project_name' => 'Project name',
            'event_date' => 'Date',
            'event_start' => 'Start',
            'event_end' => 'End',
            'event_time' => 'Time period',
            'event_type' => 'Event type',
            'bi_tags' => 'BI tags',
            'room' => 'Room',
            'area' => 'Area',
            'visitors' => 'Visitors',
            'sold_tickets' => 'Sold tickets',
            'revenue' => 'Revenue',
            'seats_capacity' => 'Number of seats',
            'occupancy_rate' => 'Occupancy rate',
        ];
    }

    /**
     * One row per event of the project within the date range, optionally
     * filtered by BI tags ('untagged' matches events whose event type has none).
     *
     * @param array<int, int|string> $tagFilter
     * @param \Illuminate\Support\Collection|null $audienceCategories
     * @return array<int, array<string, mixed>>
     */
    private function buildEventRows(
        Project $project,
        ?Carbon $from,
        ?Carbon $to,
        array $tagFilter,
        $audienceCategories = null
    ): array {
        $eventDataByEventId = $project->biEventData->keyBy('event_id');
        $capacityOverrides = $project->biRoomCapacities->keyBy('room_id');
        $categoryValuesByEvent = $project->biAudienceCategoryValues
            ->filter(static fn($value): bool => $value->scope === 'actual' && $value->event_id !== null)
            ->groupBy('event_id');

        $tagIds = array_map('intval', array_filter($tagFilter, 'is_numeric'));
        $includeUntagged = in_array('untagged', $tagFilter, true);

        return $project->events
            ->filter(function (Event $event) use ($from, $to): bool {
                if (!$event->start_time) {
                    return false;
                }

                if ($from && $event->end_time && $event->end_time->lt($from->copy()->startOfDay())) {
                    return false;
                }

                if ($to && $event->start_time->gt($to->copy()->endOfDay())) {
                    return false;
                }

                return true;
            })
            ->filter(function (Event $event) use ($tagIds, $includeUntagged): bool {
                if (empty($tagIds) && !$includeUntagged) {
                    return true;
                }

                $eventTagIds = $event->event_type?->biTags?->pluck('id')->all() ?? [];

                if (empty($eventTagIds)) {
                    return $includeUntagged;
                }

                return count(array_intersect($tagIds, $eventTagIds)) > 0;
            })
            ->sortBy('start_time')
            ->values()
            ->map(function (Event $event) use (
                $project,
                $eventDataByEventId,
                $capacityOverrides,
                $categoryValuesByEvent,
                $audienceCategories
            ): array {
                $eventData = $eventDataByEventId->get($event->id);
                $room = $event->room;

                $capacity = null;
                if ($room) {
                    $override = $capacityOverrides->get($room->id)?->capacity_override;
                    $capacity = (int) ($override ?? $room->capacity ?? 0);
                }

                $soldTickets = $eventData?->sold_tickets;

                $row = [
                    'event_id' => $event->id,
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    // Echte Datums-/Zeitzellen (Excel-Serienwert + Zellformat) statt Text
                    'event_date' => $event->start_time ? ExcelDate::PHPToExcel($event->start_time->copy()->startOfDay()) : '',
                    'event_start' => $event->start_time ? ExcelDate::PHPToExcel($event->start_time) : '',
                    'event_end' => $event->end_time ? ExcelDate::PHPToExcel($event->end_time) : '',
                    'event_time' => $this->formatEventTime($event),
                    'event_type' => $event->event_type?->name ?? '',
                    'bi_tags' => $event->event_type?->biTags
                        ?->map(fn (BiEventTypeTag $tag) => $tag->name_de ?: $tag->name)
                        ->implode(', ') ?? '',
                    'room' => $room?->name ?? '',
                    'area' => $room?->area?->name ?? '',
                    'visitors' => $eventData?->visitors ?? '',
                    'sold_tickets' => $soldTickets ?? '',
                    'revenue' => $eventData?->revenue ?? '',
                    'seats_capacity' => ($capacity ?? 0) > 0 ? $capacity : '',
                    'occupancy_rate' => self::ratio($this->biProjectMetricsService->occupancyRate(
                        $soldTickets !== null ? (int) $soldTickets : null,
                        (int) ($capacity ?? 0)
                    )),
                ];

                if ($audienceCategories) {
                    $eventCategoryValues = $categoryValuesByEvent
                        ->get($event->id)
                        ?->keyBy('bi_audience_category_id');

                    foreach ($audienceCategories as $category) {
                        $row['audience_cat_' . $category->id] =
                            $eventCategoryValues?->get($category->id)?->quantity ?? '';
                    }
                }

                return $row;
            })
            ->all();
    }

    private function formatEventTime(Event $event): string
    {
        if ($event->allDay) {
            return __('Full day');
        }

        $start = $event->start_time?->format('H:i');
        $end = $event->end_time?->format('H:i');

        if (!$start) {
            return '';
        }

        return $end ? $start . ' – ' . $end : $start;
    }

    /**
     * Central column key => label map. Single source of truth for export headers
     * and the column-selection UI. Values are translation keys.
     *
     * @return array<string, string>
     */
    public static function columnLabelMap(): array
    {
        $map = [];
        foreach (self::columnCatalog() as $group) {
            $map += $group['columns'];
        }

        return $map;
    }

    /**
     * @param array<int, string> $columns
     * @return array<string, string>
     */
    private function buildColumnLabels(array $columns): array
    {
        $static = self::columnLabelMap();
        $tags = BiEventTypeTag::all()->keyBy('id');
        $components = Component::isBiField()->pluck('name', 'id');
        $audienceCategories = BiAudienceCategory::withTrashed()->pluck('name', 'id');

        $labels = [];

        foreach ($columns as $column) {
            if (isset($static[$column])) {
                $labels[$column] = __($static[$column]);
            } elseif (str_starts_with($column, 'tag_')) {
                $tag = $tags->get((int) str_replace('tag_', '', $column));
                $labels[$column] = $tag?->name_de ?? $tag?->name ?? $column;
            } elseif (str_starts_with($column, 'custom_field_')) {
                $labels[$column] = $components->get((int) str_replace('custom_field_', '', $column)) ?? $column;
            } elseif (str_starts_with($column, 'audience_cat_')) {
                $labels[$column] = $audienceCategories
                    ->get((int) str_replace('audience_cat_', '', $column)) ?? $column;
            } else {
                $labels[$column] = $column;
            }
        }

        return $labels;
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function resolveDateRange(array $config): array
    {
        $from = !empty($config['date_from']) ? Carbon::parse($config['date_from']) : null;
        $to = !empty($config['date_to']) ? Carbon::parse($config['date_to']) : null;

        if (!$from && !empty($this->generalSettings->playing_time_window_start)) {
            $from = Carbon::parse($this->generalSettings->playing_time_window_start);
        }

        if (!$to && !empty($this->generalSettings->playing_time_window_end)) {
            $to = Carbon::parse($this->generalSettings->playing_time_window_end);
        }

        return [$from, $to];
    }

    /**
     * @param array<int, string> $columns
     * @return array<string, mixed>
     */
    private function buildProjectRow(Project $project, array $columns, ?Carbon $from, ?Carbon $to): array
    {
        $biData = $project->biData;
        $derived = $this->biDerivedValuesService->getDerivedValues($project, $from, $to);
        $tagCounts = $this->biDerivedValuesService->getTagBasedCounts($project, $from, $to);

        $visitors = $this->biProjectMetricsService->visitors($project, $from, $to);
        $soldTickets = $this->biProjectMetricsService->soldTickets($project, $from, $to);
        $revenue = $this->biProjectMetricsService->revenue($project, $from, $to);
        $capacity = $this->biProjectMetricsService->seatsCapacity($project, $from, $to);
        $avgPrice = $this->biProjectMetricsService->averagePrice($revenue, $soldTickets);
        $occupancy = $this->biProjectMetricsService->occupancyRate($soldTickets, $capacity);
        $ticketsIssued = $this->biProjectMetricsService->ticketsIssued($project, $from, $to);
        $categoryQuantities = $this->biProjectMetricsService->categoryQuantities($project, $from, $to);
        $planMetrics = $this->biProjectMetricsService->forScope('plan');
        $planComparison = $this->biProjectMetricsService->planComparison($project, $from, $to);
        $performances = $this->biProjectMetricsService->performances($project, $from, $to);

        $row = [];

        foreach ($columns as $column) {
            $row[$column] = match ($column) {
                'project_id' => $project->id,
                'project_name' => $project->name,
                'project_state' => $project->status?->name ?? '',
                'artists' => $project->artists ?? '',
                'cost_center' => $project->costCenter?->name ?? '',
                'rooms' => $this->getRoomNames($project),
                'areas' => $this->getAreaNames($project),
                'main_category' => $this->getMainCategory($project),
                'first_event_date' => $this->getFirstEventDate($project),
                'season_year' => $this->getSeasonYear($from, $to),
                'visitors' => $visitors ?? '',
                'sold_tickets' => $soldTickets ?? '',
                // Rohzahlen ausgeben: der FromView-Reader macht daraus echte Excel-Zahlen,
                // die Anzeige (€ / %) übernimmt columnFormats() im Export
                'revenue' => $revenue ?? '',
                'avg_price' => $avgPrice ?? '',
                // 0 heißt "keine Kapazität ermittelbar" (Räume ohne Kapazität, Tag nicht
                // zugeordnet) → leere Zelle statt einer scheinbar echten 0
                'seats_capacity' => $capacity > 0 ? $capacity : '',
                // Quoten als Anteil (0–1) → echtes Excel-Prozentformat
                'occupancy_rate' => self::ratio($occupancy),
                'tickets_issued' => $ticketsIssued ?? '',
                'free_tickets_rate' => self::ratio($this->biProjectMetricsService->freeTicketsRate($project, $from, $to)),
                'reduced_tickets_rate' => self::ratio($this->biProjectMetricsService
                    ->reducedTicketsRate($project, $from, $to)),
                'paying_rate' => self::ratio($this->biProjectMetricsService->payingRate($project, $from, $to)),
                'no_show_rate' => self::ratio($this->biProjectMetricsService->noShowRate($visitors, $ticketsIssued)),
                'seat_occupancy' => self::ratio($this->biProjectMetricsService
                    ->occupancyRate($ticketsIssued, $capacity)),
                'production_type' => $this->getProductionType($biData),
                // Echte Wahrheitswerte: in Excel filter- und zählbar
                'is_new_production' => (bool) $biData?->is_new_production,
                'is_co_production' => (bool) $biData?->is_co_production,
                'is_own_production' => (bool) $biData?->is_own_production,
                'is_germany_premiere' => (bool) $biData?->is_germany_premiere,
                'premiere_date' => $biData?->premiere_date ? ExcelDate::PHPToExcel($biData->premiere_date) : '',
                'contract_count' => $derived['contract_count'],
                'event_count' => $derived['event_count'],
                'booking_count' => $derived['booking_count'],
                'task_total' => $derived['task_total'],
                'task_open' => $derived['task_open'],
                'task_done' => $derived['task_done'],
                'document_count' => $derived['document_count'],
                'department_count' => $derived['department_count'],
                'user_count' => $derived['user_count'],
                'time_efforts' => $this->formatTimeEfforts($project),
                'effort_score' => $this->biDerivedValuesService->getEffortScore($project),
                'contracts_per_performance' => $performances > 0
                    ? round($derived['contract_count'] / $performances, 2)
                    : '',
                'bookings_per_performance' => $performances > 0
                    ? round($derived['booking_count'] / $performances, 2)
                    : '',
                'tasks_docs_per_production' => $derived['task_total'] + $derived['document_count'],
                'plan_visitors' => $planMetrics->visitors($project, $from, $to) ?? '',
                'plan_sold_tickets' => $planMetrics->soldTickets($project, $from, $to) ?? '',
                'plan_revenue' => $planMetrics->revenue($project, $from, $to) ?? '',
                'attainment' => self::ratio(
                    $planComparison['metrics']['revenue']['attainment']
                    ?? $planComparison['metrics']['visitors']['attainment']
                    ?? null
                ),
                default => $this->resolveTagOrCustomColumn($column, $tagCounts, $project, $categoryQuantities),
            };
        }

        return $row;
    }

    private function getRoomNames(Project $project): string
    {
        return $project->events
            ->pluck('room')
            ->filter()
            ->unique('id')
            ->pluck('name')
            ->implode(', ');
    }

    private function getAreaNames(Project $project): string
    {
        return $project->events
            ->pluck('room')
            ->filter()
            ->pluck('area')
            ->filter()
            ->unique('id')
            ->pluck('name')
            ->implode(', ');
    }

    private function getMainCategory(Project $project): string
    {
        $main = $project->categories->firstWhere('pivot.is_main', true);

        return $main?->name ?? $project->categories->first()?->name ?? '';
    }

    private function getFirstEventDate(Project $project): float|string
    {
        $dates = $project->getFirstAndLastEventDateAttribute();

        if (!$dates || empty($dates['first_event_date'])) {
            return '';
        }

        return ExcelDate::PHPToExcel(Carbon::parse($dates['first_event_date'])->startOfDay());
    }

    /** Prozentwert (0–100) → Anteil (0–1) für echtes Excel-Prozentformat; null → leere Zelle. */
    private static function ratio(int|float|null $percent): float|string
    {
        return $percent === null ? '' : round($percent / 100, 4);
    }

    private function getSeasonYear(?Carbon $from, ?Carbon $to): string
    {
        if ($from && $to) {
            return $from->year === $to->year
                ? (string) $from->year
                : $from->year . '/' . $to->year;
        }

        if ($to) {
            return (string) $to->year;
        }

        if ($from) {
            return (string) $from->year;
        }

        return '';
    }

    private function getProductionType(?BiProjectData $biData): string
    {
        if (!$biData) {
            return '';
        }

        $labels = [];

        if ($biData->is_new_production) {
            $labels[] = __('New production');
        }

        if ($biData->is_co_production) {
            $labels[] = __('Co-production');
        }

        if ($biData->is_own_production) {
            $labels[] = __('Own production');
        }

        if ($biData->is_germany_premiere) {
            $labels[] = __('Germany premiere');
        }

        return implode(', ', $labels);
    }

    private function formatTimeEfforts(Project $project): string
    {
        return $project->biTimeEfforts
            ->map(function ($effort): string {
                $bucket = $effort->effort_bucket instanceof BiEffortBucketEnum
                    ? $effort->effort_bucket->value
                    : (string) $effort->effort_bucket;

                return $effort->label . ' (' . $bucket . ')';
            })
            ->implode(', ');
    }

    /**
     * @param array<int, array<string, mixed>> $tagCounts
     * @param array<int, int> $categoryQuantities
     */
    private function resolveTagOrCustomColumn(
        string $column,
        array $tagCounts,
        Project $project,
        array $categoryQuantities = []
    ): string {
        foreach ($tagCounts as $tagCount) {
            if ($column === 'tag_' . $tagCount['tag_id']) {
                return (string) $tagCount['count'];
            }
        }

        if (str_starts_with($column, 'custom_field_')) {
            $componentId = (int) str_replace('custom_field_', '', $column);

            return $this->getCustomFieldValue($project, $componentId);
        }

        if (str_starts_with($column, 'audience_cat_')) {
            $categoryId = (int) str_replace('audience_cat_', '', $column);

            // Leerstring = nichts erfasst (unterscheidet sich bewusst von 0)
            return array_key_exists($categoryId, $categoryQuantities)
                ? (string) $categoryQuantities[$categoryId]
                : '';
        }

        return '';
    }

    private function getCustomFieldValue(Project $project, int $componentId): string
    {
        $value = ProjectComponentValue::where('project_id', $project->id)
            ->where('component_id', $componentId)
            ->first();

        if (!$value) {
            return '';
        }

        $data = $value->data ?? [];

        if (isset($data['text'])) {
            return $data['text'];
        }

        if (isset($data['checked'])) {
            return $data['checked'] ? __('Yes') : __('No');
        }

        if (isset($data['selected'])) {
            return $data['selected'];
        }

        return json_encode($data);
    }
}
