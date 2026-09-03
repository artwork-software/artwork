<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Enums\BiEffortBucketEnum;
use Artwork\Modules\BusinessIntelligence\Enums\BiVisitorModeEnum;
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
    // Score-Gewichte und Effort-Stunden leben geteilt in BiDerivedValuesService
    // (eine Wahrheit für Dashboard UND Export).

    public function __construct(
        private readonly BiProjectMetricsService $metricsService,
        private readonly BiDerivedValuesService $derivedValuesService,
        private readonly GeneralSettings $generalSettings
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(
        ?string $from = null,
        ?string $to = null,
        ?string $compareFrom = null,
        ?string $compareTo = null,
        bool $noCompare = false,
        ?string $category = null
    ): array {
        [$rangeFrom, $rangeTo] = $this->resolveDateRange($from, $to);
        $category = ($category !== null && $category !== '') ? $category : null;

        // Vergleichszeitraum: explizit gewählt, sonst Vorjahr (Default);
        // 'kein Vergleich' unterdrückt den zweiten Lauf komplett.
        $comparisonFrom = null;
        $comparisonTo = null;
        if (!$noCompare) {
            if ($compareFrom || $compareTo) {
                $comparisonFrom = $compareFrom ? Carbon::parse($compareFrom) : null;
                $comparisonTo = $compareTo ? Carbon::parse($compareTo) : null;
            } else {
                $comparisonFrom = $rangeFrom?->copy()->subYear();
                $comparisonTo = $rangeTo?->copy()->subYear();
            }
        }

        // Versions-Suffix: Schreibzugriffe bumpen bi_dashboard_version, damit
        // frisch erfasste Zahlen nicht 10 Minuten hinter dem Cache hängen.
        $cacheKey = 'bi_dashboard_'
            . ($rangeFrom?->toDateString() ?? 'null') . '_'
            . ($rangeTo?->toDateString() ?? 'null')
            . '_c' . ($comparisonFrom?->toDateString() ?? 'null')
            . '_' . ($comparisonTo?->toDateString() ?? 'null')
            . '_cat' . ($category !== null ? md5($category) : 'all')
            . '_v' . Cache::get('bi_dashboard_version', 0);

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($rangeFrom, $rangeTo, $comparisonFrom, $comparisonTo, $category): array {
                $allProjects = $this->loadProjects();
                // Sparten-Liste immer aus dem ungefilterten Zeitraum-Bestand, damit der
                // Filter auch bei aktiver Sparte umschaltbar bleibt
                $categories = $this->categoryOptions($this->projectsInRange($allProjects, $rangeFrom, $rangeTo));
                $projects = $category !== null
                    ? $allProjects
                        ->filter(fn(Project $project): bool => ($this->mainCategory($project) ?? '—') === $category)
                        ->values()
                    : $allProjects;

                $current = $this->aggregate($projects, $rangeFrom, $rangeTo);
                // Vergleich nur über datumsfilterbare Werte: TOTAL-Modus-Kennzahlen sind
                // zeitraum-neutral und würden im Vergleichs-Lauf denselben Wert liefern
                // (Delta immer 0). comparable_kpis ist das aktuelle Pendant mit
                // identischen Ausschlussregeln.
                $hasComparison = $comparisonFrom !== null || $comparisonTo !== null;
                $comparable = $hasComparison
                    ? $this->aggregateComparableKpis($projects, $rangeFrom, $rangeTo)
                    : null;
                $previous = $hasComparison
                    ? $this->aggregateComparableKpis($projects, $comparisonFrom, $comparisonTo)
                    : null;

                return [
                    'range' => [
                        'from' => $rangeFrom?->toDateString(),
                        'to' => $rangeTo?->toDateString(),
                    ],
                    'comparison_range' => $hasComparison
                        ? [
                            'from' => $comparisonFrom?->toDateString(),
                            'to' => $comparisonTo?->toDateString(),
                        ]
                        : null,
                    'kpis' => $current['kpis'],
                    'audience_quotas' => $current['audience_quotas'],
                    'plan_summary' => $current['plan_summary'],
                    'comparable_kpis' => $comparable,
                    'previous_kpis' => $previous,
                    'score_weights' => BiDerivedValuesService::SCORE_WEIGHTS,
                    'effort_hours_map' => BiDerivedValuesService::EFFORT_HOURS,
                    'by_category' => $current['by_category'],
                    'projects' => $current['projects'],
                    'monthly' => $this->buildMonthlySeries(
                        $projects,
                        $rangeFrom,
                        $rangeTo,
                        $comparisonFrom,
                        $comparisonTo
                    ),
                    'data_gaps' => $this->findDataGaps($projects, $rangeFrom, $rangeTo),
                    'category_filter' => $category,
                    'categories' => $categories,
                    // Beide KPI-Tags müssen Terminarten haben, sonst bleiben
                    // Vorstellungen/Veranstaltungstage/Auslastung leer (kein Fallback)
                    'tags_linked' => $this->metricsService->kpiTagLinked(BiProjectMetricsService::PERFORMANCE_TAG)
                        && $this->metricsService->kpiTagLinked(BiProjectMetricsService::EVENT_DAY_TAG),
                    'kpi_tags' => [
                        'performance' => $this->metricsService->kpiTagLinked(BiProjectMetricsService::PERFORMANCE_TAG),
                        'event_day' => $this->metricsService->kpiTagLinked(BiProjectMetricsService::EVENT_DAY_TAG),
                    ],
                ];
            }
        );
    }

    private function loadProjects(): Collection
    {
        return Project::query()
            ->where('is_group', false)
            ->with([
                'biData',
                'biEventData.event',
                'planBiData',
                'planBiEventData.event',
                'biRoomCapacities',
                'biAudienceCategoryValues',
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
     * Nur Projekte, die im Zeitraum stattfinden (mind. ein Termin überlappt
     * den Zeitraum) — ohne Zeitraum bleibt die volle Liste erhalten. Projekte
     * ganz ohne Termine fallen bei aktivem Filter bewusst raus, auch wenn sie
     * BI-Werte im TOTAL-Modus tragen: die sind keinem Datum zuordenbar.
     *
     * @param Collection<int, Project> $projects
     * @return Collection<int, Project>
     */
    private function projectsInRange(Collection $projects, ?Carbon $from, ?Carbon $to): Collection
    {
        if (!$from && !$to) {
            return $projects;
        }

        return $projects
            ->filter(fn(Project $project): bool => $this->eventsInRangeCount($project, $from, $to) > 0)
            ->values();
    }

    /**
     * @param Collection<int, Project> $projects
     * @return array<string, mixed>
     */
    private function aggregate(Collection $projects, ?Carbon $from, ?Carbon $to): array
    {
        $projects = $this->projectsInRange($projects, $from, $to);

        $rows = [];
        $totalVisitors = 0;
        $totalRevenue = 0.0;
        $totalCosts = 0.0;
        $anyCostsRecorded = false;
        $totalTickets = 0;
        $totalCapacity = 0;
        $totalEventDays = 0;
        $totalPerformances = 0;

        $anyVisitorsEstimated = false;

        // Kategorien-Summen (Vollzahler*innen/Ermäßigt/Freikarten) über alle Projekte
        $categoryTotals = ['full' => 0, 'reduced' => 0, 'free' => 0];
        $categoryRecorded = ['full' => false, 'reduced' => false, 'free' => false];
        $projectsWithCategories = 0;

        // Plan-Aggregation (nur Projekte mit Plan-Werten zählen in die Erreichung)
        $planMetrics = $this->metricsService->forScope('plan');
        $planVisitorsTotal = 0;
        $planRevenueTotal = 0.0;
        $planCostsTotal = 0.0;
        $actualVisitorsAgainstPlan = 0;
        $actualRevenueAgainstPlan = 0.0;
        $actualCostsAgainstPlan = 0.0;
        $projectsWithPlan = 0;

        foreach ($projects as $project) {
            ['value' => $visitorsValue, 'estimated' => $visitorsEstimated] =
                $this->metricsService->visitorsWithEstimate($project, $from, $to);
            $visitors = $visitorsValue ?? 0;
            $anyVisitorsEstimated = $anyVisitorsEstimated || $visitorsEstimated;
            $ticketsValue = $this->metricsService->soldTickets($project, $from, $to);
            $tickets = $ticketsValue ?? 0;
            $revenueValue = $this->metricsService->revenue($project, $from, $to);
            $revenue = $revenueValue ?? 0.0;
            $capacity = $this->metricsService->seatsCapacity($project, $from, $to);
            // null (nicht 0) durchreichen: Projekte ohne erfasste Tickets sollen
            // "keine Angabe" zeigen statt fälschlich 0 % Auslastung
            $occupancy = $this->metricsService->occupancyRate($ticketsValue, $capacity);

            $performances = $this->metricsService->performances($project, $from, $to);
            $eventDays = $this->metricsService->eventDays($project, $from, $to);

            // Plan-Werte je Projekt (Zeilen-Spalten + Haus-Erreichung)
            $planVisitors = $planMetrics->visitors($project, $from, $to);
            $planRevenue = $planMetrics->revenue($project, $from, $to);
            $planCosts = $planMetrics->costs($project);
            $actualCosts = $this->metricsService->costs($project);
            if ($planVisitors !== null || $planRevenue !== null || $planCosts !== null) {
                $projectsWithPlan++;
                if ($planVisitors !== null) {
                    $planVisitorsTotal += $planVisitors;
                    $actualVisitorsAgainstPlan += $visitorsValue ?? 0;
                }
                if ($planRevenue !== null) {
                    $planRevenueTotal += $planRevenue;
                    $actualRevenueAgainstPlan += $revenue;
                }
                if ($planCosts !== null) {
                    $planCostsTotal += $planCosts;
                    $actualCostsAgainstPlan += $actualCosts ?? 0.0;
                }
            }

            $categorySums = $this->metricsService->categorySums($project, $from, $to);
            $hasCategoryData = false;
            foreach ($categorySums as $type => $sum) {
                if ($sum !== null) {
                    $categoryTotals[$type] += $sum;
                    $categoryRecorded[$type] = true;
                    $hasCategoryData = true;
                }
            }
            if ($hasCategoryData) {
                $projectsWithCategories++;
            }

            $contracts = $project->contracts->count();
            $bookings = $this->derivedValuesService->getBookingCount($project);
            [$openTasks, $totalTasks] = $this->taskCounts($project);
            $documents = $project->project_files->count();
            $effortHours = $this->effortHours($project);

            $score = round(
                BiDerivedValuesService::SCORE_WEIGHTS['contracts'] * $contracts
                + BiDerivedValuesService::SCORE_WEIGHTS['bookings'] * $bookings
                + BiDerivedValuesService::SCORE_WEIGHTS['open_tasks'] * $openTasks
                + BiDerivedValuesService::SCORE_WEIGHTS['documents'] * $documents
                + BiDerivedValuesService::SCORE_WEIGHTS['effort_hours'] * $effortHours,
                1
            );

            $denominator = $performances > 0 ? $performances : null;

            $rows[] = [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'category' => $this->mainCategory($project),
                // null = nichts erfasst (Frontend zeigt "—" statt einer scheinbar echten 0)
                'visitors' => $visitorsValue,
                'visitors_estimated' => $visitorsEstimated,
                'revenue' => $revenueValue !== null ? round($revenueValue, 2) : null,
                'occupancy' => $occupancy,
                'performances' => $performances,
                'event_days' => $eventDays,
                'contracts_per_performance' => $denominator ? round($contracts / $denominator, 2) : null,
                'bookings_per_performance' => $denominator ? round($bookings / $denominator, 2) : null,
                'tasks_docs_per_production' => $totalTasks + $documents,
                'effort_score' => $score,
                'free_tickets_rate' => $this->metricsService->freeTicketsRate($project, $from, $to),
                'plan_visitors' => $planVisitors,
                'plan_revenue' => $planRevenue,
                'costs' => $actualCosts !== null ? round($actualCosts, 2) : null,
                'plan_costs' => $planCosts,
                'costs_attainment' => ($planCosts !== null && $planCosts > 0 && $actualCosts !== null)
                    ? round($actualCosts / $planCosts * 100, 1)
                    : null,
                // Erreichung bevorzugt Umsatz (Steuerungsgröße), sonst Besucher*innen
                'attainment' => match (true) {
                    $planRevenue !== null && $planRevenue > 0 => round($revenue / $planRevenue * 100, 1),
                    $planVisitors !== null && $planVisitors > 0 => round(($visitorsValue ?? 0) / $planVisitors * 100, 1),
                    default => null,
                },
            ];

            $totalVisitors += $visitors;
            $totalRevenue += $revenue;
            if ($actualCosts !== null) {
                $totalCosts += $actualCosts;
                $anyCostsRecorded = true;
            }
            $totalTickets += $tickets;
            $totalCapacity += $capacity;
            $totalEventDays += $eventDays ?? 0;
            $totalPerformances += $performances ?? 0;
        }

        // Ohne Tag-Zuordnung gibt es keine Vorstellungen/Veranstaltungstage
        // (bewusst kein Fallback auf alle Termine) → Summen bleiben null
        $performanceTagLinked = $this->metricsService->kpiTagLinked(BiProjectMetricsService::PERFORMANCE_TAG);
        $eventDayTagLinked = $this->metricsService->kpiTagLinked(BiProjectMetricsService::EVENT_DAY_TAG);

        return [
            'kpis' => [
                'visitors' => $totalVisitors,
                'visitors_estimated' => $anyVisitorsEstimated,
                'revenue' => round($totalRevenue, 2),
                // null = kein Projekt hat Kosten erfasst (Kachel wird dann nicht angezeigt)
                'costs' => $anyCostsRecorded ? round($totalCosts, 2) : null,
                'occupancy' => $totalCapacity > 0 ? round($totalTickets / $totalCapacity * 100, 1) : null,
                'event_days' => $eventDayTagLinked ? $totalEventDays : null,
                'performances' => $performanceTagLinked ? $totalPerformances : null,
                'project_count' => $projects->count(),
            ],
            'audience_quotas' => $this->buildAudienceQuotas(
                $categoryTotals,
                $categoryRecorded,
                $projectsWithCategories
            ),
            'plan_summary' => $projectsWithPlan > 0
                ? [
                    'projects_with_plan' => $projectsWithPlan,
                    'plan_visitors' => $planVisitorsTotal,
                    'plan_revenue' => round($planRevenueTotal, 2),
                    'plan_costs' => round($planCostsTotal, 2),
                    'visitors_attainment' => $planVisitorsTotal > 0
                        ? round($actualVisitorsAgainstPlan / $planVisitorsTotal * 100, 1)
                        : null,
                    'revenue_attainment' => $planRevenueTotal > 0
                        ? round($actualRevenueAgainstPlan / $planRevenueTotal * 100, 1)
                        : null,
                    'costs_attainment' => $planCostsTotal > 0
                        ? round($actualCostsAgainstPlan / $planCostsTotal * 100, 1)
                        : null,
                ]
                : null,
            'by_category' => $this->groupByCategory($rows),
            'projects' => $rows,
        ];
    }

    /**
     * Haus-Quoten aus den Kategorien-Summen; null, solange kein Projekt im
     * Zeitraum Kategoriewerte erfasst hat (Kacheln erscheinen erst mit Daten).
     *
     * @param array{full: int, reduced: int, free: int} $totals
     * @param array{full: bool, reduced: bool, free: bool} $recorded
     * @return ?array<string, mixed>
     */
    private function buildAudienceQuotas(array $totals, array $recorded, int $projectsWithCategories): ?array
    {
        if ($projectsWithCategories === 0) {
            return null;
        }

        $issued = $totals['full'] + $totals['reduced'] + $totals['free'];
        $paid = $totals['full'] + $totals['reduced'];

        return [
            'tickets_issued' => $issued,
            'full' => $recorded['full'] ? $totals['full'] : null,
            'reduced' => $recorded['reduced'] ? $totals['reduced'] : null,
            'free' => $recorded['free'] ? $totals['free'] : null,
            'free_tickets_rate' => ($recorded['free'] && $issued > 0)
                ? round($totals['free'] / $issued * 100, 1)
                : null,
            'reduced_tickets_rate' => ($recorded['reduced'] && $paid > 0)
                ? round($totals['reduced'] / $paid * 100, 1)
                : null,
            'paying_rate' => $issued > 0 ? round($paid / $issued * 100, 1) : null,
            'projects_with_categories' => $projectsWithCategories,
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
        // Auch der Vergleichslauf zählt nur Projekte, die im jeweiligen
        // Zeitraum stattfinden — sonst verwässern TOTAL-Modus-Werte von
        // Projekten außerhalb des Vergleichszeitraums das Vorjahres-Delta.
        $projects = $this->projectsInRange($projects, $from, $to);

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

            $totalEventDays += $this->metricsService->eventDays($project, $from, $to) ?? 0;
            $totalPerformances += $this->metricsService->performances($project, $from, $to) ?? 0;
        }

        return [
            'visitors' => $totalVisitors,
            'revenue' => round($totalRevenue, 2),
            'occupancy' => $totalCapacity > 0 ? round($totalTickets / $totalCapacity * 100, 1) : null,
            'event_days' => $this->metricsService->kpiTagLinked(BiProjectMetricsService::EVENT_DAY_TAG)
                ? $totalEventDays
                : null,
            'performances' => $this->metricsService->kpiTagLinked(BiProjectMetricsService::PERFORMANCE_TAG)
                ? $totalPerformances
                : null,
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

            $grouped[$key]['visitors'] += $row['visitors'] ?? 0;
            $grouped[$key]['revenue'] += $row['revenue'] ?? 0.0;
            $grouped[$key]['project_count']++;
        }

        return array_values(array_map(
            fn(array $entry) => [...$entry, 'revenue' => round($entry['revenue'], 2)],
            $grouped
        ));
    }

    /**
     * Monthly visitors/revenue from per-event entries (totals have no date and are
     * excluded by design), incl. the comparison range aligned by month INDEX —
     * so a season (Aug–Jul) can be laid over a calendar year or any free range.
     *
     * @param Collection<int, Project> $projects
     * @return array<int, array<string, mixed>>
     */
    private function buildMonthlySeries(
        Collection $projects,
        ?Carbon $from,
        ?Carbon $to,
        ?Carbon $compareFrom = null,
        ?Carbon $compareTo = null
    ): array {
        $currentBuckets = $this->bucketEventDataByMonth($projects, $from, $to);
        $previousBuckets = ($compareFrom || $compareTo)
            ? $this->bucketEventDataByMonth($projects, $compareFrom, $compareTo)
            : [];

        $months = $this->monthSequence($from, $to, array_keys($currentBuckets));
        $compareMonths = ($compareFrom || $compareTo)
            ? $this->monthSequence($compareFrom, $compareTo, array_keys($previousBuckets))
            : [];

        return array_map(function (string $month, int $index) use (
            $currentBuckets,
            $previousBuckets,
            $compareMonths
        ): array {
            // Ausrichtung über den Monats-Index ab Zeitraumbeginn, nicht über den
            // Kalendermonat — nur so sind ungleiche Zeiträume überlagerbar.
            $compareMonth = $compareMonths[$index] ?? null;

            return [
                'month' => $month,
                'compare_month' => $compareMonth,
                'visitors' => $currentBuckets[$month]['visitors'] ?? 0,
                'revenue' => round($currentBuckets[$month]['revenue'] ?? 0.0, 2),
                // null statt 0, wenn es für den Vergleichsmonat keine Daten gibt —
                // sonst zeichnet das Chart eine irreführende flache 0-Linie
                'prev_visitors' => $compareMonth !== null
                    ? ($previousBuckets[$compareMonth]['visitors'] ?? null)
                    : null,
                'prev_revenue' => ($compareMonth !== null && isset($previousBuckets[$compareMonth]))
                    ? round($previousBuckets[$compareMonth]['revenue'], 2)
                    : null,
            ];
        }, $months, array_keys($months));
    }

    /**
     * Monatsfolge eines Zeitraums (Y-m); ohne vollständigen Zeitraum die Monate
     * mit Daten. Sicherheitsgrenze 36 Monate, sonst wird das Chart unlesbar.
     *
     * @param array<int, string> $fallbackMonths
     * @return array<int, string>
     */
    private function monthSequence(?Carbon $from, ?Carbon $to, array $fallbackMonths): array
    {
        if (!$from || !$to) {
            sort($fallbackMonths);

            return $fallbackMonths;
        }

        $months = [];
        $cursor = $from->copy()->startOfMonth();
        $end = $to->copy()->startOfMonth();
        $guard = 0;
        while ($cursor->lte($end) && $guard < 36) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonth();
            $guard++;
        }

        return $months;
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

            return BiDerivedValuesService::EFFORT_HOURS[$bucket] ?? 0;
        });
    }

    /**
     * Sparten mit Projektanzahl ('—' = ohne Sparte), alphabetisch, '—' zuletzt.
     *
     * @param Collection<int, Project> $projects
     * @return array<int, array{category: string, project_count: int}>
     */
    private function categoryOptions(Collection $projects): array
    {
        $counts = [];
        foreach ($projects as $project) {
            $key = $this->mainCategory($project) ?? '—';
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $keys = array_keys($counts);
        usort($keys, static function (string $a, string $b): int {
            if ($a === '—') {
                return 1;
            }
            if ($b === '—') {
                return -1;
            }

            return strcasecmp($a, $b);
        });

        return array_map(
            static fn(string $key): array => ['category' => $key, 'project_count' => $counts[$key]],
            $keys
        );
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
