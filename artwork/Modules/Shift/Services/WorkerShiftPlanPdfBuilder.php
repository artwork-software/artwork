<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Worker\Config\WorkerEagerLoadConfig;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

readonly class WorkerShiftPlanPdfBuilder
{
    /** @var array<string, class-string<User|Freelancer|ServiceProvider>> */
    private const WORKER_MODELS = [
        'user' => User::class,
        'freelancer' => Freelancer::class,
        'service_provider' => ServiceProvider::class,
    ];

    /**
     * @param array<string, mixed> $options
     * @return array{pages: array<int, array<string, mixed>>, workerCount: int}
     */
    public function build(Carbon $startDate, Carbon $endDate, array $options): array
    {
        $workers = $this->loadWorkers(
            $startDate,
            $endDate,
            $options['worker_types'],
            $options['craft_ids'] ?? [],
        );
        $qualificationNames = ShiftQualification::query()
            ->whereIn(
                'id',
                $workers->flatMap(
                    static fn (User|Freelancer|ServiceProvider $worker): Collection => $worker->shifts
                        ->pluck('pivot.shift_qualification_id')
                )->filter()->unique()
            )
            ->pluck('name', 'id');

        $workerRows = $workers
            ->map(fn (User|Freelancer|ServiceProvider $worker): array => $this->buildWorkerRow(
                $worker,
                $startDate,
                $endDate,
                $options,
                $qualificationNames,
            ))
            ->when(
                !($options['include_empty_workers'] ?? false),
                fn (Collection $rows): Collection => $rows->filter(
                    static fn (array $row): bool => $row['hasContent']
                )
            )
            ->sortBy('sortName', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $rowsPerPage = ($options['paperSize'] ?? 'a3') === 'a3' ? 18 : 12;
        $weeks = $this->buildWeeks($startDate, $endDate);
        $pages = [];

        foreach ($weeks as $week) {
            $workerPages = $workerRows->chunk($rowsPerPage);
            if ($workerPages->isEmpty()) {
                $workerPages = collect([collect()]);
            }

            foreach ($workerPages as $pageIndex => $pageWorkers) {
                $pages[] = [
                    ...$week,
                    'workers' => $pageWorkers->values()->all(),
                    'workerPage' => $pageIndex + 1,
                    'workerPageCount' => $workerPages->count(),
                ];
            }
        }

        return [
            'pages' => $pages,
            'workerCount' => $workerRows->count(),
        ];
    }

    /**
     * @param array<int, string> $workerTypes
     * @param array<int, int> $craftIds
     * @return Collection<int, User|Freelancer|ServiceProvider>
     */
    private function loadWorkers(
        Carbon $startDate,
        Carbon $endDate,
        array $workerTypes,
        array $craftIds,
    ): Collection {
        $workers = collect();

        foreach (array_unique($workerTypes) as $workerType) {
            $modelClass = self::WORKER_MODELS[$workerType] ?? null;
            if ($modelClass === null) {
                continue;
            }

            $eagerLoads = WorkerEagerLoadConfig::getShiftPlanEagerLoads($startDate, $endDate);
            $eagerLoads['individualTimes'] = static function ($query) use ($startDate, $endDate): void {
                $query->individualByDateRange($startDate->toDateString(), $endDate->toDateString());
            };
            $typeWorkers = $modelClass::query()
                ->canWorkShifts()
                ->with($eagerLoads)
                ->get();

            if ($craftIds !== []) {
                $typeWorkers = $typeWorkers->filter(
                    static function (User|Freelancer|ServiceProvider $worker) use ($craftIds): bool {
                        $assignedCraftIds = $worker->assignedCrafts->pluck('id');
                        $shiftCraftIds = $worker->shifts->pluck('craft_id');

                        return $assignedCraftIds->merge($shiftCraftIds)->intersect($craftIds)->isNotEmpty();
                    }
                );
            }

            $workers = $workers->concat($typeWorkers);
        }

        return $workers->unique(
            static fn (User|Freelancer|ServiceProvider $worker): string => $worker::class . ':' . $worker->id
        )->values();
    }

    /**
     * @param array<string, mixed> $options
     * @param Collection<int, string> $qualificationNames
     * @return array<string, mixed>
     */
    private function buildWorkerRow(
        User|Freelancer|ServiceProvider $worker,
        Carbon $startDate,
        Carbon $endDate,
        array $options,
        Collection $qualificationNames,
    ): array {
        $cells = [];

        if ($options['show_shifts'] ?? true) {
            $craftIds = $options['craft_ids'] ?? [];
            $shifts = $craftIds === []
                ? $worker->shifts
                : $worker->shifts->whereIn('craft_id', $craftIds);

            foreach ($shifts as $shift) {
                $this->addShiftToCells(
                    $cells,
                    $shift,
                    $startDate,
                    $endDate,
                    $qualificationNames,
                );
            }
        }

        if ($options['show_individual_times'] ?? true) {
            foreach ($worker->individualTimes as $individualTime) {
                $this->addIndividualTimeToCells($cells, $individualTime, $startDate, $endDate);
            }
        }

        if ($options['show_day_services'] ?? true) {
            $this->addDayServicesToCells($cells, $worker, $startDate, $endDate);
        }

        foreach ($cells as &$cell) {
            $cell += ['shifts' => [], 'individualTimes' => [], 'dayServices' => []];
        }
        unset($cell);

        return [
            'id' => $worker->id,
            'type' => $this->workerType($worker),
            'typeLabel' => $this->workerTypeLabel($worker),
            'displayName' => $this->workerName($worker),
            'sortName' => $this->workerSortName($worker),
            'craftLabel' => $worker->assignedCrafts->pluck('name')->unique()->join(', '),
            'cells' => $cells,
            'hasContent' => $cells !== [],
        ];
    }

    /**
     * @param array<string, array<string, array<int, array<string, mixed>>>> $cells
     */
    private function addDayServicesToCells(
        array &$cells,
        User|Freelancer|ServiceProvider $worker,
        Carbon $startDate,
        Carbon $endDate,
    ): void {
        foreach ($worker->dayServices as $dayService) {
            $date = Carbon::parse($dayService->pivot->date)->toDateString();
            if ($date < $startDate->toDateString() || $date > $endDate->toDateString()) {
                continue;
            }

            $cells[$date]['dayServices'][] = [
                'name' => $dayService->name,
                'color' => $dayService->hex_color ?: '#52525b',
            ];
        }
    }

    /**
     * @param array<string, array<string, array<int, array<string, mixed>>>> $cells
     * @param Collection<int, string> $qualificationNames
     */
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.CyclomaticComplexity.TooHigh
    private function addShiftToCells(
        array &$cells,
        Shift $shift,
        Carbon $rangeStart,
        Carbon $rangeEnd,
        Collection $qualificationNames,
    ): void {
        $pivot = $shift->pivot;
        $startDateValue = $pivot?->start_date ?? $shift->start_date ?? $shift->event_start_day;
        $endDateValue = $pivot?->end_date ?? $shift->end_date ?? $shift->event_end_day ?? $startDateValue;

        if ($startDateValue === null) {
            return;
        }

        $startDate = Carbon::parse($startDateValue)->startOfDay();
        $endDate = Carbon::parse($endDateValue)->startOfDay();
        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy();
        }

        $visibleStart = $startDate->max($rangeStart->copy()->startOfDay());
        $visibleEnd = $endDate->min($rangeEnd->copy()->startOfDay());
        if ($visibleEnd->lt($visibleStart)) {
            return;
        }

        $startTime = $this->formatTime($pivot?->start_time ?? $shift->start) ?? '00:00';
        $endTime = $this->formatTime($pivot?->end_time ?? $shift->end) ?? '24:00';
        $room = $shift->event?->room ?? $shift->room;
        $qualificationName = $pivot?->shift_qualification_id
            ? $qualificationNames->get($pivot->shift_qualification_id)
            : null;

        foreach (CarbonPeriod::create($visibleStart, $visibleEnd) as $day) {
            $date = $day->format('Y-m-d');
            [$dayStart, $dayEnd] = $this->timesForDay($day, $startDate, $endDate, $startTime, $endTime);

            $cells[$date]['shifts'][] = [
                'room' => $room?->name ?? __('Without room'),
                'start' => $dayStart,
                'end' => $dayEnd,
                'craft' => $shift->craft?->abbreviation ?? $shift->craft?->name,
                'function' => $qualificationName,
                'description' => $pivot?->short_description ?: $shift->description,
                'committed' => (bool) $shift->is_committed,
                'inWorkflow' => (bool) $shift->in_workflow,
            ];
        }
    }

    /**
     * @param array<string, array<string, array<int, array<string, mixed>>>> $cells
     */
    private function addIndividualTimeToCells(
        array &$cells,
        IndividualTime $individualTime,
        Carbon $rangeStart,
        Carbon $rangeEnd,
    ): void {
        $startDate = Carbon::parse($individualTime->start_date)->startOfDay();
        $endDate = Carbon::parse($individualTime->end_date ?? $individualTime->start_date)->startOfDay();
        $visibleStart = $startDate->max($rangeStart->copy()->startOfDay());
        $visibleEnd = $endDate->min($rangeEnd->copy()->startOfDay());

        if ($visibleEnd->lt($visibleStart)) {
            return;
        }

        $startTime = $this->formatTime($individualTime->start_time) ?? '00:00';
        $endTime = $this->formatTime($individualTime->end_time) ?? '24:00';

        foreach (CarbonPeriod::create($visibleStart, $visibleEnd) as $day) {
            $date = $day->format('Y-m-d');
            [$dayStart, $dayEnd] = $this->timesForDay($day, $startDate, $endDate, $startTime, $endTime);

            $cells[$date]['individualTimes'][] = [
                'title' => $individualTime->title,
                'fullDay' => (bool) $individualTime->full_day,
                'start' => $dayStart,
                'end' => $dayEnd,
            ];
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function timesForDay(
        Carbon $day,
        Carbon $startDate,
        Carbon $endDate,
        string $startTime,
        string $endTime,
    ): array {
        if ($startDate->isSameDay($endDate)) {
            return [$startTime, $endTime];
        }

        return [
            $day->isSameDay($startDate) ? $startTime : '00:00',
            $day->isSameDay($endDate) ? $endTime : '24:00',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildWeeks(Carbon $startDate, Carbon $endDate): array
    {
        $holidayMap = $this->holidayMap($startDate, $endDate);
        $weeks = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $day) {
            $key = $day->isoWeekYear . '-' . $day->isoWeek;
            $weeks[$key] ??= [
                'weekNumber' => $day->isoWeek,
                'weekYear' => $day->isoWeekYear,
                'days' => [],
            ];
            $date = $day->format('Y-m-d');
            $weeks[$key]['days'][] = [
                'date' => $date,
                'dayName' => $day->translatedFormat('D'),
                'dateLabel' => $day->format('d.m.Y'),
                'isWeekend' => $day->isWeekend(),
                'holiday' => $holidayMap[$date] ?? null,
            ];
        }

        return array_values($weeks);
    }

    /** @return array<string, string> */
    private function holidayMap(Carbon $startDate, Carbon $endDate): array
    {
        $map = [];
        $holidays = Holiday::query()
            ->whereDate('date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->get(['name', 'date', 'end_date']);

        foreach ($holidays as $holiday) {
            $holidayStart = Carbon::parse($holiday->date)->max($startDate);
            $holidayEnd = Carbon::parse($holiday->end_date ?? $holiday->date)->min($endDate);
            foreach (CarbonPeriod::create($holidayStart, $holidayEnd) as $day) {
                $map[$day->format('Y-m-d')] = $holiday->name;
            }
        }

        return $map;
    }

    private function workerName(User|Freelancer|ServiceProvider $worker): string
    {
        if ($worker instanceof ServiceProvider) {
            return $worker->provider_name;
        }

        return trim($worker->first_name . ' ' . $worker->last_name);
    }

    private function workerSortName(User|Freelancer|ServiceProvider $worker): string
    {
        if ($worker instanceof ServiceProvider) {
            return $worker->provider_name;
        }

        return trim($worker->last_name . ', ' . $worker->first_name);
    }

    private function workerType(User|Freelancer|ServiceProvider $worker): string
    {
        return match (true) {
            $worker instanceof User => 'user',
            $worker instanceof Freelancer => 'freelancer',
            default => 'service_provider',
        };
    }

    private function workerTypeLabel(User|Freelancer|ServiceProvider $worker): string
    {
        return match (true) {
            $worker instanceof User => __('Internal'),
            $worker instanceof Freelancer => __('Freelancer'),
            default => __('Service provider'),
        };
    }

    private function formatTime(mixed $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        return substr((string) $time, 0, 5);
    }
}
