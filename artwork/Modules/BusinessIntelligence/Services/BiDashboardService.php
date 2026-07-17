<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Enums\BiEffortBucketEnum;
use Artwork\Modules\BusinessIntelligence\Enums\BiVisitorModeEnum;
use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Project\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Tenant-wide BI aggregation for the dashboard ("Interne Steuerung"):
 * house KPIs, per-category breakdown, the effort-vs-output drilldown table
 * (incl. the weighted Aufwand-Proxy-Score) and a year-over-year comparison.
 */
class BiDashboardService
{
    /**
     * Representative hours per effort bucket, used to fold the manual
     * time-effort buckets into the proxy score.
     */
    private const EFFORT_HOURS = [
        '0-10' => 5,
        '10-25' => 17.5,
        '25-50' => 37.5,
        '50-100' => 75,
        '100+' => 125,
    ];

    /**
     * Weights of the Aufwand-Proxy-Score. Shipped to the frontend so the score
     * can be explained instead of appearing as a black-box number.
     */
    private const SCORE_WEIGHTS = [
        'contracts' => 2,
        'bookings' => 1,
        'open_tasks' => 1.5,
        'documents' => 0.5,
        'effort_hours' => 0.1,
    ];

    public function __construct(
        private readonly BiProjectMetricsService $metricsService,
        private readonly BiDerivedValuesService $derivedValuesService,
        private readonly GeneralSettings $generalSettings
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(?string $from = null, ?string $to = null): array
    {
        [$rangeFrom, $rangeTo] = $this->resolveDateRange($from, $to);

        // Versions-Suffix: Schreibzugriffe bumpen bi_dashboard_version, damit
        // frisch erfasste Zahlen nicht 10 Minuten hinter dem Cache hängen.
        $cacheKey = 'bi_dashboard_'
            . ($rangeFrom?->toDateString() ?? 'null') . '_'
            . ($rangeTo?->toDateString() ?? 'null')
            . '_v' . Cache::get('bi_dashboard_version', 0);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($rangeFrom, $rangeTo): array {
            $projects = $this->loadProjects();

            $current = $this->aggregate($projects, $rangeFrom, $rangeTo);
            // Jahresvergleich nur über datumsfilterbare Werte: TOTAL-Modus-Kennzahlen sind
            // zeitraum-neutral und würden im Vorjahres-Lauf denselben Wert liefern (Delta immer 0).
            // comparable_kpis ist das aktuelle Pendant mit identischen Ausschlussregeln.
            $comparable = $this->aggregateComparableKpis($projects, $rangeFrom, $rangeTo);
            $previous = $this->aggregateComparableKpis(
                $projects,
                $rangeFrom?->copy()->subYear(),
                $rangeTo?->copy()->subYear()
            );

            return [
                'range' => [
                    'from' => $rangeFrom?->toDateString(),
                    'to' => $rangeTo?->toDateString(),
                ],
                'kpis' => $current['kpis'],
                'comparable_kpis' => $comparable,
                'previous_kpis' => $previous,
                'score_weights' => self::SCORE_WEIGHTS,
                'effort_hours_map' => self::EFFORT_HOURS,
                'by_category' => $current['by_category'],
                'projects' => $current['projects'],
                'monthly' => $this->buildMonthlySeries($projects, $rangeFrom, $rangeTo),
                'data_gaps' => $this->findDataGaps($projects, $rangeFrom, $rangeTo),
                'tags_linked' => BiEventTypeTag::has('eventTypes')->exists(),
            ];
        });
    }

    private function loadProjects(): Collection
    {
        return Project::query()
            ->where('is_group', false)
            ->with([
                'biData',
                'biEventData.event',
                'biRoomCapacities',
                'biTimeEfforts',
                'events.room',
                'events.event_type.biTags',
                'contracts',
                'checklists.tasks',
                'project_files',
                'categories',
                'table',
            ])
            ->get();
    }

    /**
     * @param Collection<int, Project> $projects
     * @return array<string, mixed>
     */
    private function aggregate(Collection $projects, ?Carbon $from, ?Carbon $to): array
    {
        $rows = [];
        $totalVisitors = 0;
        $totalRevenue = 0.0;
        $totalTickets = 0;
        $totalCapacity = 0;
        $totalEventDays = 0;
        $totalPerformances = 0;

        $anyVisitorsEstimated = false;

        foreach ($projects as $project) {
            ['value' => $visitorsValue, 'estimated' => $visitorsEstimated] =
                $this->metricsService->visitorsWithEstimate($project, $from, $to);
            $visitors = $visitorsValue ?? 0;
            $anyVisitorsEstimated = $anyVisitorsEstimated || $visitorsEstimated;
            $ticketsValue = $this->metricsService->soldTickets($project, $from, $to);
            $tickets = $ticketsValue ?? 0;
            $revenue = $this->metricsService->revenue($project, $from, $to) ?? 0.0;
            $capacity = $this->metricsService->seatsCapacity($project, $from, $to);
            // null (nicht 0) durchreichen: Projekte ohne erfasste Tickets sollen
            // "keine Angabe" zeigen statt fälschlich 0 % Auslastung
            $occupancy = $this->metricsService->occupancyRate($ticketsValue, $capacity);

            $performances = $this->metricsService->performances($project, $from, $to);
            $eventDays = $this->metricsService->eventDays($project, $from, $to);

            $contracts = $project->contracts->count();
            $bookings = $this->derivedValuesService->getBookingCount($project);
            [$openTasks, $totalTasks] = $this->taskCounts($project);
            $documents = $project->project_files->count();
            $effortHours = $this->effortHours($project);

            $score = round(
                self::SCORE_WEIGHTS['contracts'] * $contracts
                + self::SCORE_WEIGHTS['bookings'] * $bookings
                + self::SCORE_WEIGHTS['open_tasks'] * $openTasks
                + self::SCORE_WEIGHTS['documents'] * $documents
                + self::SCORE_WEIGHTS['effort_hours'] * $effortHours,
                1
            );

            $denominator = $performances > 0 ? $performances : null;

            $rows[] = [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'category' => $this->mainCategory($project),
                'visitors' => $visitors,
                'visitors_estimated' => $visitorsEstimated,
                'revenue' => round($revenue, 2),
                'occupancy' => $occupancy,
                'performances' => $performances,
                'event_days' => $eventDays,
                'contracts_per_performance' => $denominator ? round($contracts / $denominator, 2) : null,
                'bookings_per_performance' => $denominator ? round($bookings / $denominator, 2) : null,
                'tasks_docs_per_production' => $totalTasks + $documents,
                'effort_score' => $score,
            ];

            $totalVisitors += $visitors;
            $totalRevenue += $revenue;
            $totalTickets += $tickets;
            $totalCapacity += $capacity;
            $totalEventDays += $eventDays;
            $totalPerformances += $performances;
        }

        return [
            'kpis' => [
                'visitors' => $totalVisitors,
                'visitors_estimated' => $anyVisitorsEstimated,
                'revenue' => round($totalRevenue, 2),
                'occupancy' => $totalCapacity > 0 ? round($totalTickets / $totalCapacity * 100, 1) : null,
                'event_days' => $totalEventDays,
                'performances' => $totalPerformances,
                'project_count' => $projects->count(),
            ],
            'by_category' => $this->groupByCategory($rows),
            'projects' => $rows,
        ];
    }

    /**
     * KPI-only aggregation for the year-over-year comparison. Metrics whose mode
     * is TOTAL are skipped per project: totals carry no date, so "previous year"
     * would return the identical value and the delta would be meaningless.
     * Deliberately computes none of the effort/booking figures (they are
     * range-independent), which keeps the second pass cheap.
     *
     * @param Collection<int, Project> $projects
     * @return array<string, mixed>
     */
    private function aggregateComparableKpis(Collection $projects, ?Carbon $from, ?Carbon $to): array
    {
        $totalVisitors = 0;
        $totalRevenue = 0.0;
        $totalTickets = 0;
        $totalCapacity = 0;
        $totalEventDays = 0;
        $totalPerformances = 0;
        $excludedProjects = 0;

        foreach ($projects as $project) {
            $biData = $project->biData;
            $visitorsTimeNeutral = $biData?->visitor_mode === BiVisitorModeEnum::TOTAL;
            $ticketsTimeNeutral = $biData?->sold_tickets_mode === BiVisitorModeEnum::TOTAL;
            $revenueTimeNeutral = $biData?->revenue_mode === BiVisitorModeEnum::TOTAL;

            if ($visitorsTimeNeutral || $ticketsTimeNeutral || $revenueTimeNeutral) {
                $excludedProjects++;
            }

            if (!$visitorsTimeNeutral) {
                // Der ≈-Fallback schätzt aus Ticketzahlen — wenn die im TOTAL-Modus
                // gepflegt werden, wäre auch die Schätzung zeitraum-neutral.
                $visitorsValue = $ticketsTimeNeutral
                    ? $this->metricsService->visitors($project, $from, $to)
                    : $this->metricsService->visitorsWithEstimate($project, $from, $to)['value'];
                $totalVisitors += $visitorsValue ?? 0;
            }

            if (!$ticketsTimeNeutral) {
                $totalTickets += $this->metricsService->soldTickets($project, $from, $to) ?? 0;
                $totalCapacity += $this->metricsService->seatsCapacity($project, $from, $to);
            }

            if (!$revenueTimeNeutral) {
                $totalRevenue += $this->metricsService->revenue($project, $from, $to) ?? 0.0;
            }

            $totalEventDays += $this->metricsService->eventDays($project, $from, $to);
            $totalPerformances += $this->metricsService->performances($project, $from, $to);
        }

        return [
            'visitors' => $totalVisitors,
            'revenue' => round($totalRevenue, 2),
            'occupancy' => $totalCapacity > 0 ? round($totalTickets / $totalCapacity * 100, 1) : null,
            'event_days' => $totalEventDays,
            'performances' => $totalPerformances,
            'excluded_total_mode_projects' => $excludedProjects,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function groupByCategory(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $key = $row['category'] ?: '—';

            if (!isset($grouped[$key])) {
                $grouped[$key] = ['category' => $key, 'visitors' => 0, 'revenue' => 0.0, 'project_count' => 0];
            }

            $grouped[$key]['visitors'] += $row['visitors'];
            $grouped[$key]['revenue'] += $row['revenue'];
            $grouped[$key]['project_count']++;
        }

        return array_values(array_map(
            fn(array $entry) => [...$entry, 'revenue' => round($entry['revenue'], 2)],
            $grouped
        ));
    }

    /**
     * Monthly visitors/revenue from per-event entries (totals have no date and are
     * excluded by design), incl. the same month one year earlier for comparison.
     *
     * @param Collection<int, Project> $projects
     * @return array<int, array<string, mixed>>
     */
    private function buildMonthlySeries(Collection $projects, ?Carbon $from, ?Carbon $to): array
    {
        $currentBuckets = $this->bucketEventDataByMonth($projects, $from, $to);
        $previousBuckets = $this->bucketEventDataByMonth(
            $projects,
            $from?->copy()->subYear(),
            $to?->copy()->subYear()
        );

        $months = array_keys($currentBuckets);

        if ($from && $to) {
            $months = [];
            $cursor = $from->copy()->startOfMonth();
            $end = $to->copy()->startOfMonth();
            // Sicherheitsgrenze: höchstens 36 Monatsspalten, sonst wird das Chart unlesbar
            $guard = 0;
            while ($cursor->lte($end) && $guard < 36) {
                $months[] = $cursor->format('Y-m');
                $cursor->addMonth();
                $guard++;
            }
        } else {
            sort($months);
        }

        return array_map(function (string $month) use ($currentBuckets, $previousBuckets): array {
            $prevMonth = Carbon::createFromFormat('Y-m', $month)->subYear()->format('Y-m');

            return [
                'month' => $month,
                'visitors' => $currentBuckets[$month]['visitors'] ?? 0,
                'revenue' => round($currentBuckets[$month]['revenue'] ?? 0.0, 2),
                // null statt 0, wenn es für den Vorjahresmonat keine Daten gibt —
                // sonst zeichnet das Chart eine irreführende flache 0-Linie
                'prev_visitors' => $previousBuckets[$prevMonth]['visitors'] ?? null,
                'prev_revenue' => isset($previousBuckets[$prevMonth])
                    ? round($previousBuckets[$prevMonth]['revenue'], 2)
                    : null,
            ];
        }, $months);
    }

    /**
     * @param Collection<int, Project> $projects
     * @return array<string, array{visitors: int, revenue: float}>
     */
    private function bucketEventDataByMonth(Collection $projects, ?Carbon $from, ?Carbon $to): array
    {
        $buckets = [];

        foreach ($projects as $project) {
            foreach ($project->biEventData as $eventData) {
                $start = $eventData->event?->start_time;

                if (!$start) {
                    continue;
                }

                if ($from && $start->lt($from->copy()->startOfDay())) {
                    continue;
                }

                if ($to && $start->gt($to->copy()->endOfDay())) {
                    continue;
                }

                $month = $start->format('Y-m');
                $buckets[$month] ??= ['visitors' => 0, 'revenue' => 0.0];
                $buckets[$month]['visitors'] += (int) ($eventData->visitors ?? 0);
                $buckets[$month]['revenue'] += (float) ($eventData->revenue ?? 0);
            }
        }

        return $buckets;
    }

    /**
     * Projects with events in the range but no single BI figure recorded —
     * these silently drag every dashboard aggregate towards zero.
     *
     * @param Collection<int, Project> $projects
     * @return array<int, array<string, mixed>>
     */
    private function findDataGaps(Collection $projects, ?Carbon $from, ?Carbon $to): array
    {
        $gaps = [];

        foreach ($projects as $project) {
            if ($this->eventsInRangeCount($project, $from, $to) === 0) {
                continue;
            }

            $biData = $project->biData;
            $hasTotals = $biData && (
                $biData->visitors_total !== null
                || $biData->sold_tickets_total !== null
                || $biData->revenue_total !== null
            );
            $hasEventData = $project->biEventData->contains(
                fn($entry) => $entry->visitors !== null
                    || $entry->sold_tickets !== null
                    || $entry->revenue !== null
            );
            // Alle drei Kennzahlen bewusst als "nicht relevant" markiert → keine Lücke
            $allNotApplicable = $biData
                && $biData->visitors_not_applicable
                && $biData->sold_tickets_not_applicable
                && $biData->revenue_not_applicable;

            if (!$hasTotals && !$hasEventData && !$allNotApplicable) {
                $gaps[] = [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                ];
            }
        }

        return $gaps;
    }

    private function eventsInRangeCount(Project $project, ?Carbon $from, ?Carbon $to): int
    {
        return $project->events->filter(function ($event) use ($from, $to): bool {
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
        })->count();
    }

    /**
     * @return array{0: int, 1: int} [openTasks, totalTasks]
     */
    private function taskCounts(Project $project): array
    {
        $total = 0;
        $done = 0;

        foreach ($project->checklists as $checklist) {
            foreach ($checklist->tasks as $task) {
                $total++;
                if ($task->done) {
                    $done++;
                }
            }
        }

        return [$total - $done, $total];
    }

    private function effortHours(Project $project): float
    {
        return (float) $project->biTimeEfforts->sum(function ($effort): float {
            $bucket = $effort->effort_bucket instanceof BiEffortBucketEnum
                ? $effort->effort_bucket->value
                : (string) $effort->effort_bucket;

            return self::EFFORT_HOURS[$bucket] ?? 0;
        });
    }

    private function mainCategory(Project $project): ?string
    {
        $main = $project->categories->firstWhere('pivot.is_main', true);

        return $main?->name ?? $project->categories->first()?->name;
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function resolveDateRange(?string $from, ?string $to): array
    {
        $rangeFrom = $from ? Carbon::parse($from) : null;
        $rangeTo = $to ? Carbon::parse($to) : null;

        if (!$rangeFrom && !empty($this->generalSettings->playing_time_window_start)) {
            $rangeFrom = Carbon::parse($this->generalSettings->playing_time_window_start);
        }

        if (!$rangeTo && !empty($this->generalSettings->playing_time_window_end)) {
            $rangeTo = Carbon::parse($this->generalSettings->playing_time_window_end);
        }

        return [$rangeFrom, $rangeTo];
    }
}
