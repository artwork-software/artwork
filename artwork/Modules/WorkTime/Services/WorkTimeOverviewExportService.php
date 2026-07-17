<?php

namespace Artwork\Modules\WorkTime\Services;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkTimeOverviewExportService
{
    /**
     * @param array<int> $craftIds empty array exports all crafts
     * @return array{
     *     crafts: Collection<int, array{id: int, name: string}>,
     *     rows: Collection<int, array<string, mixed>>
     * }
     */
    public function buildMatrix(
        Carbon $rangeStart,
        Carbon $rangeEnd,
        array $craftIds,
        string $language = 'de'
    ): array {
        $rangeStart = $rangeStart->copy()->startOfMonth();
        $rangeEnd = $rangeEnd->copy()->endOfMonth();

        $months = [];
        $cursor = $rangeStart->copy();
        while ($cursor->lessThan($rangeEnd)) {
            $months[] = $cursor->copy();
            $cursor->addMonth();
        }

        $crafts = Craft::query()
            ->without(['craftShiftPlaner', 'craftInventoryPlaner'])
            ->with([
                'users' => fn ($query) => $query
                    ->without('shiftQualifications')
                    ->select(['users.id', 'users.is_freelancer']),
                'freelancers' => fn ($query) => $query
                    ->without('shiftQualifications')
                    ->select('freelancers.id'),
                'serviceProviders' => fn ($query) => $query
                    ->without('shiftQualifications')
                    ->select('service_providers.id'),
            ])
            ->when($craftIds !== [], fn ($query) => $query->whereIn('id', $craftIds))
            ->orderBy('position')
            ->get();

        $userIds = $crafts->flatMap(fn (Craft $craft) => $craft->users->pluck('id'))->unique()->values();
        $freelancerIds = $crafts->flatMap(fn (Craft $craft) => $craft->freelancers->pluck('id'))->unique()->values();
        $serviceProviderIds = $crafts
            ->flatMap(fn (Craft $craft) => $craft->serviceProviders->pluck('id'))
            ->unique()
            ->values();
        $selectedCraftIds = $crafts->pluck('id')->all();

        $bookingsByUserAndMonth = $this->bookingMinutesByUserAndMonth($userIds->all(), $rangeStart, $rangeEnd);
        $shiftMinutesByType = [
            User::class => $this->shiftMinutesByWorkerAndMonth(
                User::class,
                $userIds->all(),
                $selectedCraftIds,
                $rangeStart,
                $rangeEnd,
            ),
            Freelancer::class => $this->shiftMinutesByWorkerAndMonth(
                Freelancer::class,
                $freelancerIds->all(),
                $selectedCraftIds,
                $rangeStart,
                $rangeEnd,
            ),
            ServiceProvider::class => $this->shiftMinutesByWorkerAndMonth(
                ServiceProvider::class,
                $serviceProviderIds->all(),
                $selectedCraftIds,
                $rangeStart,
                $rangeEnd,
            ),
        ];

        // Reihenfolge der Gewerks-Mitgliedschaften pro User (Position der Gewerke),
        // als Fallback für die Buchungs-Attribution
        $craftsByUser = [];
        foreach ($crafts as $craft) {
            foreach ($craft->users->unique('id') as $user) {
                $craftsByUser[$user->id][] = $craft->id;
            }
        }

        $rows = collect();
        $yearAccumulator = [];
        $currentYear = null;

        foreach ($months as $month) {
            $monthKey = $month->format('Y-m');

            $bookingAttribution = $this->bookingAttributionByUser(
                $monthKey,
                $bookingsByUserAndMonth,
                $shiftMinutesByType[User::class],
                $craftsByUser,
            );

            if ($currentYear !== null && $month->year !== $currentYear) {
                $rows->push($this->yearSumRow($currentYear, $yearAccumulator));
                $yearAccumulator = [];
            }
            $currentYear = $month->year;

            $cells = [];
            foreach ($crafts as $craft) {
                $cell = $this->craftCell(
                    $craft,
                    $monthKey,
                    $bookingsByUserAndMonth,
                    $shiftMinutesByType,
                    $bookingAttribution,
                );

                $cells[$craft->id] = $cell;
                $yearAccumulator[$craft->id] = $this->addCells(
                    $yearAccumulator[$craft->id] ?? $this->emptyCell(),
                    $cell,
                );
            }

            $rows->push([
                'label' => $month->locale($language)->translatedFormat('F Y'),
                'is_sum' => false,
                'cells' => $cells,
                'total' => $this->totalCell($cells),
            ]);
        }

        if ($currentYear !== null) {
            $rows->push($this->yearSumRow($currentYear, $yearAccumulator));
        }

        return [
            'crafts' => $crafts->map(fn (Craft $craft) => ['id' => $craft->id, 'name' => $craft->name])->values(),
            'rows' => $rows,
        ];
    }

    /**
     * @param array<int, array<string, array{soll: int, ist: int}>> $bookings
     * @param array<class-string, array<int, array<int, array<string, int>>>> $shiftMinutesByType
     * @return array{soll_intern: int, ist_intern: int, soll_extern: int, ist_extern: int}
     */
    private function craftCell(
        Craft $craft,
        string $month,
        array $bookings,
        array $shiftMinutesByType,
        array $bookingAttribution,
    ): array {
        $cell = $this->emptyCell();

        foreach ($craft->users->unique('id') as $user) {
            $cell = $this->addCells(
                $cell,
                $this->userCell(
                    $user,
                    $craft->id,
                    $month,
                    $bookings,
                    $shiftMinutesByType[User::class][$craft->id] ?? [],
                    $bookingAttribution,
                ),
            );
        }

        $cell['ist_extern'] += $this->sumShiftMinutes(
            $craft->freelancers->unique('id'),
            $shiftMinutesByType[Freelancer::class][$craft->id] ?? [],
            $month,
        );
        $cell['ist_extern'] += $this->sumShiftMinutes(
            $craft->serviceProviders->unique('id'),
            $shiftMinutesByType[ServiceProvider::class][$craft->id] ?? [],
            $month,
        );

        return $cell;
    }

    /**
     * @param array<int, array<string, array{soll: int, ist: int}>> $bookings
     * @param array<int, array<string, int>> $shiftMinutes
     * @return array{soll_intern: int, ist_intern: int, soll_extern: int, ist_extern: int}
     */
    private function userCell(
        User $user,
        int $craftId,
        string $month,
        array $bookings,
        array $shiftMinutes,
        array $bookingAttribution,
    ): array {
        $booking = $bookings[$user->id][$month] ?? null;
        $cell = $this->emptyCell();

        // Buchungen (Soll/Ist) sind pro User, nicht pro Gewerk: sie zählen nur im
        // Attributions-Gewerk, sonst fließen Personen in mehreren Gewerken mehrfach
        // in Gesamt- und Jahressummen ein
        if ($booking !== null && ($bookingAttribution[$user->id] ?? null) !== $craftId) {
            return $cell;
        }

        $actualMinutes = $booking['ist'] ?? $shiftMinutes[$user->id][$month] ?? 0;

        if ($user->is_freelancer) {
            $cell['soll_extern'] = $booking['soll'] ?? 0;
            $cell['ist_extern'] = $actualMinutes;

            return $cell;
        }

        $cell['soll_intern'] = $booking['soll'] ?? 0;
        $cell['ist_intern'] = $actualMinutes;

        return $cell;
    }

    /**
     * Ordnet die Monats-Buchung jedes Users genau einem Gewerk zu: dem mit den meisten
     * Schichtminuten des Users in diesem Monat, sonst dem ersten Mitglieds-Gewerk.
     *
     * @param array<int, array<string, array{soll: int, ist: int}>> $bookings
     * @param array<int, array<int, array<string, int>>> $shiftMinutesByCraft [craftId][userId][Y-m] => minutes
     * @param array<int, array<int>> $craftsByUser userId => craftIds in Gewerk-Reihenfolge
     * @return array<int, int> userId => craftId
     */
    private function bookingAttributionByUser(
        string $month,
        array $bookings,
        array $shiftMinutesByCraft,
        array $craftsByUser,
    ): array {
        $attribution = [];

        foreach ($bookings as $userId => $byMonth) {
            if (!isset($byMonth[$month]) || !isset($craftsByUser[$userId])) {
                continue;
            }

            $bestCraftId = $craftsByUser[$userId][0];
            $bestMinutes = -1;
            foreach ($craftsByUser[$userId] as $craftId) {
                $minutes = $shiftMinutesByCraft[$craftId][$userId][$month] ?? 0;
                if ($minutes > $bestMinutes) {
                    $bestMinutes = $minutes;
                    $bestCraftId = $craftId;
                }
            }

            $attribution[$userId] = $bestCraftId;
        }

        return $attribution;
    }

    /**
     * @param Collection<int, User|Freelancer|ServiceProvider> $workers
     * @param array<int, array<string, int>> $shiftMinutes
     */
    private function sumShiftMinutes(Collection $workers, array $shiftMinutes, string $month): int
    {
        return $workers->sum(
            fn (User|Freelancer|ServiceProvider $worker): int => $shiftMinutes[$worker->id][$month] ?? 0,
        );
    }

    /**
     * @return array{soll_intern: int, ist_intern: int, soll_extern: int, ist_extern: int}
     */
    private function emptyCell(): array
    {
        return ['soll_intern' => 0, 'ist_intern' => 0, 'soll_extern' => 0, 'ist_extern' => 0];
    }

    /**
     * @param array{soll_intern: int, ist_intern: int, soll_extern: int, ist_extern: int} $left
     * @param array{soll_intern: int, ist_intern: int, soll_extern: int, ist_extern: int} $right
     * @return array{soll_intern: int, ist_intern: int, soll_extern: int, ist_extern: int}
     */
    private function addCells(array $left, array $right): array
    {
        return [
            'soll_intern' => $left['soll_intern'] + $right['soll_intern'],
            'ist_intern' => $left['ist_intern'] + $right['ist_intern'],
            'soll_extern' => $left['soll_extern'] + $right['soll_extern'],
            'ist_extern' => $left['ist_extern'] + $right['ist_extern'],
        ];
    }

    /**
     * @param array<int, array{soll_intern: int, ist_intern: int, soll_extern: int, ist_extern: int}> $cells
     * @return array{soll_intern: int, ist_intern: int, soll_extern: int, ist_extern: int}
     */
    private function totalCell(array $cells): array
    {
        return array_reduce(
            $cells,
            fn (array $total, array $cell) => $this->addCells($total, $cell),
            $this->emptyCell(),
        );
    }

    /**
     * @param array<int, array{soll_intern: int, ist_intern: int, soll_extern: int, ist_extern: int}> $yearAccumulator
     * @return array<string, mixed>
     */
    private function yearSumRow(int $year, array $yearAccumulator): array
    {
        return [
            'label' => (string) $year,
            'is_sum' => true,
            'cells' => $yearAccumulator,
            'total' => $this->totalCell($yearAccumulator),
        ];
    }

    /**
     * @param array<int> $userIds
     * @return array<int, array<string, array{soll: int, ist: int}>> [userId][Y-m] => minutes
     */
    private function bookingMinutesByUserAndMonth(array $userIds, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        if ($userIds === []) {
            return [];
        }

        $sums = [];

        WorkTimeBooking::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('booking_day', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->selectRaw(
                'user_id, ' .
                "DATE_FORMAT(booking_day, '%Y-%m') as month, " .
                'SUM(wanted_working_hours) as soll_minutes, ' .
                'SUM(worked_hours) as ist_minutes'
            )
            ->groupBy('user_id', 'month')
            ->get()
            ->each(function ($sum) use (&$sums): void {
                $sums[(int) $sum->user_id][(string) $sum->month] = [
                    'soll' => (int) $sum->soll_minutes,
                    'ist' => (int) $sum->ist_minutes,
                ];
            });

        return $sums;
    }

    /**
     * @param class-string<User|Freelancer|ServiceProvider> $employableType
     * @param array<int> $workerIds
     * @param array<int> $craftIds
     * @return array<int, array<int, array<string, int>>> [craftId][workerId][Y-m] => minutes
     */
    private function shiftMinutesByWorkerAndMonth(
        string $employableType,
        array $workerIds,
        array $craftIds,
        Carbon $rangeStart,
        Carbon $rangeEnd
    ): array {
        if ($workerIds === []) {
            return [];
        }

        $minutesByWorker = [];
        $overlapQueryStart = $rangeStart->copy()->subDay();

        ShiftWorker::query()
            ->where('employable_type', $employableType)
            ->whereIn('employable_id', $workerIds)
            ->where('start_date', '<=', $rangeEnd->toDateString())
            ->where('end_date', '>=', $overlapQueryStart->toDateString())
            ->whereHas('shift', fn ($query) => $query->whereIn('craft_id', $craftIds))
            ->with('shift:id,craft_id,break_minutes')
            ->get()
            ->each(function (ShiftWorker $worker) use (
                &$minutesByWorker,
                $employableType,
                $rangeStart,
                $rangeEnd,
            ): void {
                if (!$worker->start_date || !$worker->end_date || !$worker->start_time || !$worker->end_time) {
                    return;
                }

                $start = $worker->start_date->copy()->setTimeFromTimeString((string) $worker->start_time);
                $end = $worker->end_date->copy()->setTimeFromTimeString((string) $worker->end_time);
                if ($end->lessThanOrEqualTo($start)) {
                    $end = $end->addDay();
                }

                $workerId = (int) $worker->employable_id;
                $craftId = (int) $worker->shift->craft_id;
                $headcount = $employableType === ServiceProvider::class
                    ? max(1, (int) ($worker->shift_count ?? 1))
                    : 1;

                foreach ($this->workedMinutesByMonth(
                    $start,
                    $end,
                    (int) ($worker->shift?->break_minutes ?? 0),
                    $rangeStart,
                    $rangeEnd,
                ) as $monthKey => $minutes) {
                    $minutesByWorker[$craftId][$workerId][$monthKey] =
                        ($minutesByWorker[$craftId][$workerId][$monthKey] ?? 0) + ($minutes * $headcount);
                }
            });

        return $minutesByWorker;
    }

    /**
     * Splits a shift across calendar months and distributes its break proportionally.
     * The proportional distribution is deterministic and preserves the exact total
     * number of worked minutes even when rounding is required.
     *
     * @return array<string, int> [Y-m] => minutes
     */
    private function workedMinutesByMonth(
        Carbon $start,
        Carbon $end,
        int $breakMinutes,
        Carbon $rangeStart,
        Carbon $rangeEnd,
    ): array {
        $segments = [];
        $cursor = $start->copy();

        while ($cursor->lessThan($end)) {
            $nextMonth = $cursor->copy()->startOfMonth()->addMonth();
            $segmentEnd = $nextMonth->lessThan($end) ? $nextMonth : $end->copy();
            $segments[] = [
                'month' => $cursor->format('Y-m'),
                'start' => $cursor->copy(),
                'minutes' => (int) $cursor->diffInMinutes($segmentEnd),
            ];
            $cursor = $segmentEnd;
        }

        $totalMinutes = array_sum(array_column($segments, 'minutes'));
        if ($totalMinutes <= 0) {
            return [];
        }

        $workedMinutes = max(0, $totalMinutes - max(0, $breakMinutes));
        $distributedMinutes = 0;

        foreach ($segments as $index => $segment) {
            $weightedMinutes = $segment['minutes'] * $workedMinutes;
            $segments[$index]['worked_minutes'] = intdiv($weightedMinutes, $totalMinutes);
            $segments[$index]['remainder'] = $weightedMinutes % $totalMinutes;
            $distributedMinutes += $segments[$index]['worked_minutes'];
        }

        $roundingMinutes = $workedMinutes - $distributedMinutes;
        $segmentIndexes = array_keys($segments);
        usort($segmentIndexes, function (int $left, int $right) use ($segments): int {
            return $segments[$right]['remainder'] <=> $segments[$left]['remainder']
                ?: $left <=> $right;
        });

        for ($index = 0; $index < $roundingMinutes; $index++) {
            $segments[$segmentIndexes[$index]]['worked_minutes']++;
        }

        $exportStart = $rangeStart->copy()->startOfDay();
        $exportEnd = $rangeEnd->copy()->addDay()->startOfDay();
        $result = [];

        foreach ($segments as $segment) {
            if ($segment['start']->lessThan($exportStart) || !$segment['start']->lessThan($exportEnd)) {
                continue;
            }

            $result[$segment['month']] = ($result[$segment['month']] ?? 0) + $segment['worked_minutes'];
        }

        return $result;
    }
}
