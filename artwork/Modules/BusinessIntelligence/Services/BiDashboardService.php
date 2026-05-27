<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Enums\BiEffortBucketEnum;
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

        $cacheKey = 'bi_dashboard_' . ($rangeFrom?->toDateString() ?? 'null') . '_' . ($rangeTo?->toDateString() ?? 'null');

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($rangeFrom, $rangeTo): array {
            $projects = $this->loadProjects();

            $current = $this->aggregate($projects, $rangeFrom, $rangeTo);
            $previous = $this->aggregate(
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
                'previous_kpis' => $previous['kpis'],
                'by_category' => $current['by_category'],
                'projects' => $current['projects'],
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

        foreach ($projects as $project) {
            $visitors = $this->metricsService->visitors($project, $from, $to) ?? 0;
            $tickets = $this->metricsService->soldTickets($project, $from, $to) ?? 0;
            $revenue = $this->metricsService->revenue($project, $from, $to) ?? 0.0;
            $capacity = $this->metricsService->seatsCapacity($project);
            $occupancy = $this->metricsService->occupancyRate($tickets, $capacity);

            $performances = $this->countPerformances($project, $from, $to);
            $eventDays = $this->countEventDays($project, $from, $to);

            $contracts = $project->contracts->count();
            $bookings = $this->derivedValuesService->getBookingCount($project);
            [$openTasks, $totalTasks] = $this->taskCounts($project);
            $documents = $project->project_files->count();
            $effortHours = $this->effortHours($project);

            $score = round(
                2 * $contracts
                + 1 * $bookings
                + 1.5 * $openTasks
                + 0.5 * $documents
                + 0.1 * $effortHours,
                1
            );

            $denominator = $performances > 0 ? $performances : null;

            $rows[] = [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'category' => $this->mainCategory($project),
                'visitors' => $visitors,
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

    private function countPerformances(Project $project, ?Carbon $from, ?Carbon $to): int
    {
        $events = $this->eventsInRange($project, $from, $to);
        $tagged = $events->filter(fn($event) => $this->eventHasTag($event, 'Vorstellung'));

        return $tagged->isNotEmpty() ? $tagged->count() : $events->count();
    }

    private function countEventDays(Project $project, ?Carbon $from, ?Carbon $to): int
    {
        $events = $this->eventsInRange($project, $from, $to);
        $tagged = $events->filter(fn($event) => $this->eventHasTag($event, 'Veranstaltungstag'));
        $relevant = $tagged->isNotEmpty() ? $tagged : $events;

        return $relevant
            ->map(fn($event) => $event->start_time?->format('Y-m-d'))
            ->filter()
            ->unique()
            ->count();
    }

    private function eventsInRange(Project $project, ?Carbon $from, ?Carbon $to)
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
        });
    }

    private function eventHasTag($event, string $name): bool
    {
        $tags = $event->event_type?->biTags;

        if (!$tags) {
            return false;
        }

        return $tags->contains(
            fn($tag) => strcasecmp($tag->name_de ?? '', $name) === 0
                || strcasecmp($tag->name ?? '', $name) === 0
        );
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
