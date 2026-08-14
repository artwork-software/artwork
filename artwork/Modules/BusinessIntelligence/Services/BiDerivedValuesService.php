<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\BusinessIntelligence\Repositories\BiEventTypeTagRepository;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Services\ColumnRelevanceService;
use Artwork\Modules\Project\Models\Project;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiDerivedValuesService
{
    /**
     * Gewichte des Aufwand-Proxy-Scores und Stunden-Repräsentanten der
     * Effort-Buckets — geteilte Wahrheit für Dashboard UND Export.
     */
    public const SCORE_WEIGHTS = [
        'contracts' => 2,
        'bookings' => 1,
        'open_tasks' => 1.5,
        'documents' => 0.5,
        'effort_hours' => 0.1,
    ];

    public const EFFORT_HOURS = [
        '0-10' => 5,
        '10-25' => 17.5,
        '25-50' => 37.5,
        '50-100' => 75,
        '100+' => 125,
    ];

    public function __construct(
        private readonly BiEventTypeTagRepository $biEventTypeTagRepository
    ) {
    }

    /**
     * Sage-Bezug nur anbieten, wenn die Schnittstelle wirklich aktiv ist
     * (gleiche Logik wie HandleInertiaRequests → sageApiEnabled-Prop).
     */
    public function isSageApiEnabled(): bool
    {
        if (!config('services.sage.enabled')) {
            return false;
        }

        $settings = app(\Artwork\Modules\SageApiSettings\Services\SageApiSettingsService::class)->getFirst();

        return $settings !== null && $settings->enabled;
    }

    /**
     * Gewichteter Aufwand-Proxy-Score eines Projekts (identische Formel wie im
     * Dashboard — eine gemeinsame Methode, kein Nachbau im Export).
     */
    public function getEffortScore(Project $project): float
    {
        $contracts = $project->contracts->count();
        $bookings = $this->getBookingCount($project);
        $taskCounts = $this->getTaskCounts($project);
        $documents = $project->project_files->count();
        $effortHours = $this->getEffortHours($project);

        return round(
            self::SCORE_WEIGHTS['contracts'] * $contracts
            + self::SCORE_WEIGHTS['bookings'] * $bookings
            + self::SCORE_WEIGHTS['open_tasks'] * $taskCounts['open']
            + self::SCORE_WEIGHTS['documents'] * $documents
            + self::SCORE_WEIGHTS['effort_hours'] * $effortHours,
            1
        );
    }

    public function getEffortHours(Project $project): float
    {
        return (float) $project->biTimeEfforts->sum(function ($effort): float {
            $bucket = $effort->effort_bucket instanceof \Artwork\Modules\BusinessIntelligence\Enums\BiEffortBucketEnum
                ? $effort->effort_bucket->value
                : (string) $effort->effort_bucket;

            return self::EFFORT_HOURS[$bucket] ?? 0;
        });
    }

    /**
     * @return array<string, int>
     */
    public function getDerivedValues(Project $project, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $taskCounts = $this->getTaskCounts($project);

        return [
            'contract_count' => $project->contracts()->count(),
            'event_count' => $this->scopeEventsByRange($project->events(), $from, $to)->count(),
            'booking_count' => $this->getBookingCount($project),
            'task_total' => $taskCounts['total'],
            'task_open' => $taskCounts['open'],
            'task_done' => $taskCounts['done'],
            'document_count' => $project->project_files()->count(),
            'department_count' => $project->departments()->count(),
            'user_count' => $project->users()->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTagBasedCounts(Project $project, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $tags = $this->biEventTypeTagRepository->getAllWithEventTypes();
        $counts = [];

        foreach ($tags as $tag) {
            $counts[] = [
                'tag_id' => $tag->id,
                'tag_name' => $tag->name,
                'tag_name_de' => $tag->name_de,
                'color' => $tag->color,
                'count' => $this->countDistinctDaysForTag($project, $tag, $from, $to),
            ];
        }

        return $counts;
    }

    private function countDistinctDaysForTag(
        Project $project,
        BiEventTypeTag $tag,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): int {
        $eventTypeIds = $tag->eventTypes->pluck('id')->toArray();

        if (empty($eventTypeIds)) {
            return 0;
        }

        $events = $this->scopeEventsByRange($project->events(), $from, $to)
            ->whereIn('event_type_id', $eventTypeIds)
            ->get(['start_time', 'end_time']);

        $uniqueDates = collect();

        foreach ($events as $event) {
            // start_time/end_time sind nullable — ohne beide Daten ist keine Tageszählung möglich
            if (!$event->start_time || !$event->end_time) {
                continue;
            }

            $start = $event->start_time->copy()->startOfDay();
            $end = $event->end_time->copy()->startOfDay();

            if ($from && $start->lt($from->copy()->startOfDay())) {
                $start = $from->copy()->startOfDay();
            }

            if ($to && $end->gt($to->copy()->startOfDay())) {
                $end = $to->copy()->startOfDay();
            }

            if ($end->lt($start)) {
                continue;
            }

            foreach (CarbonPeriod::create($start, $end) as $date) {
                $uniqueDates->push($date->format('Y-m-d'));
            }
        }

        return $uniqueDates->unique()->count();
    }

    /**
     * Limits an events relation to those overlapping the given date range.
     */
    private function scopeEventsByRange(HasMany $query, ?Carbon $from, ?Carbon $to): HasMany
    {
        if ($from) {
            $query->where('end_time', '>=', $from->copy()->startOfDay());
        }

        if ($to) {
            $query->where('start_time', '<=', $to->copy()->endOfDay());
        }

        return $query;
    }

    public function getBookingCount(Project $project): int
    {
        // Property-Zugriff nutzt die eager-geladene Relation (table()->first() feuert immer eine frische Query)
        $table = $project->table;

        if (!$table) {
            return 0;
        }

        $cellIds = ColumnCell::whereIn(
            'sub_position_row_id',
            SubPositionRow::whereIn(
                'sub_position_id',
                SubPosition::whereIn(
                    'main_position_id',
                    MainPosition::where('table_id', $table->id)->select('id')
                )->select('id')
            )->select('id')
        )->select('id');

        return SageAssignedData::whereIn('column_cell_id', $cellIds)->count();
    }

    /**
     * Umsatz-Vorschläge aus dem Budget-Modul für die Plan/Ist-Erfassung:
     * plan = Einnahmen-Summe der budgetrelevanten Spalte (identisch zu den
     * Stichtag-Exporten; Fallback Altbestand: letzte Wertspalte), actual =
     * Erlös-Summe der Sage-Buchungen (negative buchungsbetrag-Werte sind
     * Erlöse). null = keine Quelle vorhanden.
     *
     * @return array{plan: ?float, actual: ?float}
     */
    public function getRevenueSuggestions(Project $project): array
    {
        $table = $project->table;

        if (!$table) {
            return ['plan' => null, 'actual' => null];
        }

        $table->loadMissing('columns');

        $plan = null;
        $forecastColumn = app(ColumnRelevanceService::class)->resolveRelevantColumn($table->columns);
        if ($forecastColumn) {
            $earnings = (float) ($table->earningSums[$forecastColumn->id] ?? 0);
            // 0 heißt praktisch "keine Einnahmen kalkuliert" — kein sinnvoller Vorschlag
            $plan = $earnings > 0 ? round($earnings, 2) : null;
        }

        $actual = null;
        // Ist-Vorschlag nur mit aktiver Sage-Schnittstelle — sonst gäbe es
        // einen "Erlöse aus Sage"-Chip, obwohl das Haus gar kein Sage nutzt
        $sageColumn = $this->isSageApiEnabled()
            ? $table->columns->first(fn($column) => $column->type === 'sage')
            : null;
        if ($sageColumn) {
            $sageColumn->loadMissing('cells.sageAssignedData');
            $revenue = 0.0;
            $hasBookings = false;
            foreach ($sageColumn->cells as $cell) {
                foreach ($cell->sageAssignedData as $booking) {
                    $hasBookings = true;
                    $value = (float) $booking->buchungsbetrag;
                    if ($value < 0) {
                        $revenue -= $value;
                    }
                }
            }
            $actual = ($hasBookings && $revenue > 0) ? round($revenue, 2) : null;
        }

        return ['plan' => $plan, 'actual' => $actual];
    }

    /**
     * Kosten-Vorschläge aus dem Budget-Modul, spiegelbildlich zu
     * getRevenueSuggestions(): costs_plan = Kosten-Summe der budgetrelevanten
     * Spalte, costs_actual = Kosten-Summe der Sage-Buchungen (positive
     * buchungsbetrag-Werte sind Kosten). null = keine Quelle vorhanden.
     *
     * @return array{costs_plan: ?float, costs_actual: ?float}
     */
    public function getCostSuggestions(Project $project): array
    {
        $table = $project->table;

        if (!$table) {
            return ['costs_plan' => null, 'costs_actual' => null];
        }

        $table->loadMissing('columns');

        $plan = null;
        $forecastColumn = app(ColumnRelevanceService::class)->resolveRelevantColumn($table->columns);
        if ($forecastColumn) {
            $costs = (float) ($table->costSums[$forecastColumn->id] ?? 0);
            $plan = $costs > 0 ? round($costs, 2) : null;
        }

        $actual = null;
        $sageColumn = $this->isSageApiEnabled()
            ? $table->columns->first(fn($column) => $column->type === 'sage')
            : null;
        if ($sageColumn) {
            $sageColumn->loadMissing('cells.sageAssignedData');
            $costsSum = 0.0;
            $hasBookings = false;
            foreach ($sageColumn->cells as $cell) {
                foreach ($cell->sageAssignedData as $booking) {
                    $hasBookings = true;
                    $value = (float) $booking->buchungsbetrag;
                    if ($value > 0) {
                        $costsSum += $value;
                    }
                }
            }
            $actual = ($hasBookings && $costsSum > 0) ? round($costsSum, 2) : null;
        }

        return ['costs_plan' => $plan, 'costs_actual' => $actual];
    }

    /**
     * @return array{total: int, open: int, done: int}
     */
    private function getTaskCounts(Project $project): array
    {
        $checklists = $project->checklists()->with('tasks')->get();

        $total = 0;
        $done = 0;

        foreach ($checklists as $checklist) {
            foreach ($checklist->tasks as $task) {
                $total++;
                if ($task->done) {
                    $done++;
                }
            }
        }

        return [
            'total' => $total,
            'open' => $total - $done,
            'done' => $done,
        ];
    }
}
