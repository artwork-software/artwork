<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Enums\BiEffortBucketEnum;
use Artwork\Modules\BusinessIntelligence\Exports\BiExportWorkbook;
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
    public function exportConfigurationOptions(): array
    {
        // Sage-basierte Spalten nur anbieten, wenn die Schnittstelle aktiv ist
        $sageColumns = ['booking_count', 'bookings_per_performance'];
        $sageEnabled = $this->biDerivedValuesService->isSageApiEnabled();

        $columns = collect(self::columnLabelMap())
            ->when(!$sageEnabled, fn ($collection) => $collection->except($sageColumns))
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

        $presets = BiExportPreset::query()->orderBy('name')->get(['id', 'name', 'columns', 'is_shared']);

        return [
            'columns' => $columns,
            'tagColumns' => $tagColumns,
            'customFieldColumns' => $customFieldColumns,
            'audienceCategoryColumns' => $audienceCategoryColumns,
            'presets' => $presets,
        ];
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

            Cache::put('bi_export_status_' . $token, ['status' => 'ready'], now()->addMinutes(60));
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
        return Cache::get('bi_export_status_' . $token) ?? ['status' => 'unknown'];
    }

    public function downloadStored(string $token): BinaryFileResponse
    {
        $path = $this->storagePath($token);

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Export not found or expired.');
        }

        $filename = 'bi-export-' . now()->format('Y-m-d_His') . '.xlsx';

        return response()
            ->download(Storage::disk('local')->path($path), $filename)
            ->deleteFileAfterSend();
    }

    private function storagePath(string $token): string
    {
        return 'bi-exports/' . $token . '.xlsx';
    }

    private function buildExport(array $config): BiExportWorkbook
    {
        [$from, $to] = $this->resolveDateRange($config);

        $granularity = $config['granularity'] ?? 'both';
        $columns = $config['columns'] ?? [];
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

        return new BiExportWorkbook($sheets);
    }

    /**
     * Fixed column set of the per-event sheet. Values are translation keys.
     *
     * @return array<string, string>
     */
    public static function eventColumnLabelMap(): array
    {
        return [
            'project_name' => 'Project name',
            'event_date' => 'Date',
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
                    'project_name' => $project->name,
                    'event_date' => $event->start_time?->format('d.m.Y') ?? '',
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
                    'seats_capacity' => $capacity ?? '',
                    'occupancy_rate' => $this->biProjectMetricsService->occupancyRate(
                        $soldTickets !== null ? (int) $soldTickets : null,
                        (int) ($capacity ?? 0)
                    ) ?? '',
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
        return [
            'project_name' => 'Project name',
            'project_state' => 'Project status',
            'artists' => 'Artist / Group',
            'cost_center' => 'Cost bearer',
            'rooms' => 'Room',
            'areas' => 'Area',
            'main_category' => 'Category (Sector)',
            'first_event_date' => 'First performance',
            'season_year' => 'Year',
            'visitors' => 'Visitors',
            'sold_tickets' => 'Sold tickets',
            'revenue' => 'Revenue',
            'avg_price' => 'Average price',
            'seats_capacity' => 'Number of seats',
            'occupancy_rate' => 'Occupancy rate',
            'tickets_issued' => 'Tickets issued',
            'free_tickets_rate' => 'Free ticket rate',
            'reduced_tickets_rate' => 'Reduced ticket rate',
            'paying_rate' => 'Paying rate',
            'no_show_rate' => 'No-show rate',
            'seat_occupancy' => 'Seat occupancy (incl. free tickets)',
            'production_type' => 'Production type',
            'is_new_production' => 'New production',
            'is_co_production' => 'Co-production',
            'is_own_production' => 'Own production',
            'is_germany_premiere' => 'Germany premiere',
            'premiere_date' => 'Premiere date',
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
            'plan_visitors' => 'Plan visitors',
            'plan_sold_tickets' => 'Plan sold tickets',
            'plan_revenue' => 'Plan revenue',
            'attainment' => 'Attainment',
        ];
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
                'occupancy_rate' => $occupancy ?? '',
                'tickets_issued' => $ticketsIssued ?? '',
                'free_tickets_rate' => $this->biProjectMetricsService->freeTicketsRate($project, $from, $to) ?? '',
                'reduced_tickets_rate' => $this->biProjectMetricsService
                    ->reducedTicketsRate($project, $from, $to) ?? '',
                'paying_rate' => $this->biProjectMetricsService->payingRate($project, $from, $to) ?? '',
                'no_show_rate' => $this->biProjectMetricsService->noShowRate($visitors, $ticketsIssued) ?? '',
                'seat_occupancy' => $this->biProjectMetricsService
                    ->occupancyRate($ticketsIssued, $capacity) ?? '',
                'production_type' => $this->getProductionType($biData),
                'is_new_production' => $biData?->is_new_production ? 'Ja' : 'Nein',
                'is_co_production' => $biData?->is_co_production ? 'Ja' : 'Nein',
                'is_own_production' => $biData?->is_own_production ? 'Ja' : 'Nein',
                'is_germany_premiere' => $biData?->is_germany_premiere ? 'Ja' : 'Nein',
                'premiere_date' => $biData?->premiere_date?->format('d.m.Y') ?? '',
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
                'attainment' => $planComparison['metrics']['revenue']['attainment']
                    ?? $planComparison['metrics']['visitors']['attainment']
                    ?? '',
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

    private function getFirstEventDate(Project $project): string
    {
        $dates = $project->getFirstAndLastEventDateAttribute();

        if (!$dates || empty($dates['first_event_date'])) {
            return '';
        }

        return Carbon::parse($dates['first_event_date'])->format('d.m.Y');
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
            $labels[] = 'Neuproduktion';
        }

        if ($biData->is_co_production) {
            $labels[] = 'Coproduktion';
        }

        if ($biData->is_own_production) {
            $labels[] = 'Eigenproduktion';
        }

        if ($biData->is_germany_premiere) {
            $labels[] = 'Deutschlandpremiere';
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
            return $data['checked'] ? 'Ja' : 'Nein';
        }

        if (isset($data['selected'])) {
            return $data['selected'];
        }

        return json_encode($data);
    }
}
