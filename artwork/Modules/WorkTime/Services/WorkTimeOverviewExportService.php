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
            ->without(['craftShiftPlaner'])
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
            // Tage mit Tagesbuchung kommen aus der Buchung; nur die übrigen Tage zählen Schichtminuten
            User::class => $this->shiftMinutesByWorkerAndMonth(
                User::class,
                $userIds->all(),
                $selectedCraftIds,
                $rangeStart,
                $rangeEnd,
                $this->bookedDaysByUser($userIds->all(), $rangeStart, $rangeEnd),
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

        // Zuordnung der Buchung zum Gewerk nach ALLEN Schichtminuten des Monats (auch gebuchter Tage)
        $allShiftMinutesOfUsers = $this->shiftMinutesByWorkerAndMonth(
            User::class,
            $userIds->all(),
            $selectedCraftIds,
            $rangeStart,
            $rangeEnd,
        );

        // Reihenfolge der Gewerks-Mitgliedschaften pro User (Position der Gewerke),
        // als Fallback für die Buchungs-Attribution
        $craftsByUser = [];
        $primaryCraftByWorker = [];
        foreach ($crafts as $craft) {
            foreach ($craft->users->unique('id') as $user) {
                $craftsByUser[$user->id][] = $craft->id;
            }
            foreach ($craft->freelancers->unique('id') as $freelancer) {
                $primaryCraftByWorker[Freelancer::class][$freelancer->id] ??= $craft->id;
            }
            foreach ($craft->serviceProviders->unique('id') as $serviceProvider) {
                $primaryCraftByWorker[ServiceProvider::class][$serviceProvider->id] ??= $craft->id;
            }
        }

        // Individuelle Zeiten (Proben, Termine ohne Schicht) zählen wie im Arbeitszeiten-Tab mit – bei Usern nur
        // an Tagen ohne Tagesbuchung (dort stecken sie schon in der Buchung), je Person in genau einem Gewerk
        $individualMinutesByType = [
            User::class => $this->individualMinutesByWorkerAndMonth(
                $crafts->flatMap(fn (Craft $craft) => $craft->users)->unique('id'),
                $rangeStart,
                $rangeEnd,
                $this->bookedDaysByUser($userIds->all(), $rangeStart, $rangeEnd),
            ),
            Freelancer::class => $this->individualMinutesByWorkerAndMonth(
                $crafts->flatMap(fn (Craft $craft) => $craft->freelancers)->unique('id'),
                $rangeStart,
                $rangeEnd,
            ),
            ServiceProvider::class => $this->individualMinutesByWorkerAndMonth(
                $crafts->flatMap(fn (Craft $craft) => $craft->serviceProviders)->unique('id'),
                $rangeStart,
                $rangeEnd,
            ),
        ];

        $rows = collect();
        $yearAccumulator = [];
        $currentYear = null;

        foreach ($months as $month) {
            $monthKey = $month->format('Y-m');

            $bookingAttribution = $this->bookingAttributionByUser(
                $monthKey,
                $bookingsByUserAndMonth,
                $allShiftMinutesOfUsers,
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
                $cell = $this->addIndividualMinutes(
                    $cell,
                    $craft,
                    $monthKey,
                    $individualMinutesByType,
                    $bookingAttribution,
                    $craftsByUser,
                    $primaryCraftByWorker,
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
        // in Gesamt- und Jahressummen ein. Schichtminuten nicht gebuchter Tage zählen im Gewerk der Schicht.
        $countsBooking = $booking !== null && ($bookingAttribution[$user->id] ?? null) === $craftId;
        $soll = $countsBooking ? $booking['soll'] : 0;
        $actualMinutes = ($countsBooking ? $booking['ist'] : 0) + ($shiftMinutes[$user->id][$month] ?? 0);

        if ($user->is_freelancer) {
            $cell['soll_extern'] = $soll;
            $cell['ist_extern'] = $actualMinutes;

            return $cell;
        }

        $cell['soll_intern'] = $soll;
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
            // Korrekturbuchungen (Altbestand) tragen ihr Delta nur im Saldo, nicht in worked_hours
            ->selectRaw(
                'user_id, ' .
                "DATE_FORMAT(booking_day, '%Y-%m') as month, " .
                'SUM(wanted_working_hours) as soll_minutes, ' .
                "SUM(CASE WHEN name LIKE 'adjustment\\_%' THEN work_time_balance_change ELSE worked_hours END)" .
                ' as ist_minutes'
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
     * Individuelle Minuten im Gewerk der Person addieren: User im Gewerk der Buchungs-Zuordnung (sonst erstes
     * Mitglieds-Gewerk), Externe im ersten Gewerk – so zählt jede Zeit genau einmal.
     *
     * @param array{soll_intern: int, ist_intern: int, soll_extern: int, ist_extern: int} $cell
     * @param array<class-string, array<int, array<string, int>>> $individualMinutesByType
     * @param array<int, int> $bookingAttribution
     * @param array<int, array<int>> $craftsByUser
     * @param array<class-string, array<int, int>> $primaryCraftByWorker
     * @return array{soll_intern: int, ist_intern: int, soll_extern: int, ist_extern: int}
     */
    private function addIndividualMinutes(
        array $cell,
        Craft $craft,
        string $month,
        array $individualMinutesByType,
        array $bookingAttribution,
        array $craftsByUser,
        array $primaryCraftByWorker,
    ): array {
        foreach (collect($craft->users)->unique('id') as $user) {
            $primaryCraft = $bookingAttribution[$user->id] ?? ($craftsByUser[$user->id][0] ?? null);
            if ($primaryCraft !== $craft->id) {
                continue;
            }
            $minutes = $individualMinutesByType[User::class][$user->id][$month] ?? 0;
            $cell[$user->is_freelancer ? 'ist_extern' : 'ist_intern'] += $minutes;
        }

        $externalsByType = [
            Freelancer::class => collect($craft->freelancers),
            ServiceProvider::class => collect($craft->serviceProviders),
        ];
        foreach ($externalsByType as $type => $workers) {
            foreach ($workers->unique('id') as $worker) {
                if (($primaryCraftByWorker[$type][$worker->id] ?? null) === $craft->id) {
                    $cell['ist_extern'] += $individualMinutesByType[$type][$worker->id][$month] ?? 0;
                }
            }
        }

        return $cell;
    }

    /**
     * @param iterable<User|Freelancer|ServiceProvider> $workers
     * @param array<int, array<string, true>> $skipDaysByWorker
     * @return array<int, array<string, int>> [workerId][Y-m] => minutes
     */
    private function individualMinutesByWorkerAndMonth(
        iterable $workers,
        Carbon $rangeStart,
        Carbon $rangeEnd,
        array $skipDaysByWorker = [],
    ): array {
        $calculation = app(WorkTimeCalculationService::class);
        $result = [];
        foreach ($workers as $worker) {
            $perDay = $calculation->individualMinutesPerDay($worker, $rangeStart->copy(), $rangeEnd->copy());
            foreach ($perDay as $day => $minutes) {
                if ($minutes <= 0 || isset($skipDaysByWorker[$worker->id][$day])) {
                    continue;
                }
                $month = substr($day, 0, 7);
                $result[$worker->id][$month] = ($result[$worker->id][$month] ?? 0) + $minutes;
            }
        }

        return $result;
    }

    /**
     * Tage mit Tagesbuchung je User (Altdaten ohne Namen zählen ebenfalls als Tagesbuchung).
     *
     * @param array<int> $userIds
     * @return array<int, array<string, true>> [userId]['Y-m-d'] => true
     */
    private function bookedDaysByUser(array $userIds, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        if ($userIds === []) {
            return [];
        }

        $days = [];
        WorkTimeBooking::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('booking_day', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->where(fn ($query) => $query
                ->whereNull('name')
                ->orWhere('name', 'like', 'daily\\_work\\_time\\_booking\\_%'))
            ->get(['user_id', 'booking_day'])
            ->each(function (WorkTimeBooking $booking) use (&$days): void {
                $days[(int) $booking->user_id][$booking->booking_day->toDateString()] = true;
            });

        return $days;
    }

    /**
     * @param class-string<User|Freelancer|ServiceProvider> $employableType
     * @param array<int> $workerIds
     * @param array<int> $craftIds
     * @param array<int, array<string, true>> $skipDaysByWorker Tage, die schon aus der Buchung kommen
     * @return array<int, array<int, array<string, int>>> [craftId][workerId][Y-m] => minutes
     */
    private function shiftMinutesByWorkerAndMonth(
        string $employableType,
        array $workerIds,
        array $craftIds,
        Carbon $rangeStart,
        Carbon $rangeEnd,
        array $skipDaysByWorker = []
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
                $skipDaysByWorker,
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

                foreach (
                    $this->workedMinutesByMonth(
                        $start,
                        $end,
                        (int) ($worker->shift?->break_minutes ?? 0),
                        $rangeStart,
                        $rangeEnd,
                        $skipDaysByWorker[$workerId] ?? [],
                    ) as $monthKey => $minutes
                ) {
                    $minutesByWorker[$craftId][$workerId][$monthKey] =
                        ($minutesByWorker[$craftId][$workerId][$monthKey] ?? 0) + ($minutes * $headcount);
                }
            });

        return $minutesByWorker;
    }

    /**
     * Splits a shift across calendar days, deducts the break from the first day on (remainder carries over
     * to the next day, like the time account), then sums per month. Days in $skipDays are left out
     * (their hours already come from the daily booking).
     *
     * @param array<string, true> $skipDays ['Y-m-d' => true]
     * @return array<string, int> [Y-m] => minutes
     */
    private function workedMinutesByMonth(
        Carbon $start,
        Carbon $end,
        int $breakMinutes,
        Carbon $rangeStart,
        Carbon $rangeEnd,
        array $skipDays = [],
    ): array {
        $segments = [];
        $cursor = $start->copy();

        while ($cursor->lessThan($end)) {
            $nextDay = $cursor->copy()->startOfDay()->addDay();
            $segmentEnd = $nextDay->lessThan($end) ? $nextDay : $end->copy();
            $segments[] = [
                'month' => $cursor->format('Y-m'),
                'day' => $cursor->toDateString(),
                'start' => $cursor->copy(),
                'minutes' => (int) $cursor->diffInMinutes($segmentEnd),
            ];
            $cursor = $segmentEnd;
        }

        if ($segments === []) {
            return [];
        }

        // Pause wie im Zeitkonto (WorkTimeCalculationService): ab dem ersten Tag abziehen, was dort keinen Platz
        // hat, am Folgetag – sonst weichen Export und Arbeitszeiten-Tab bei Schichten über Mitternacht ab
        $remainingBreak = max(0, $breakMinutes);
        foreach ($segments as $index => $segment) {
            $deducted = min($remainingBreak, $segment['minutes']);
            $segments[$index]['worked_minutes'] = $segment['minutes'] - $deducted;
            $remainingBreak -= $deducted;
        }

        $exportStart = $rangeStart->copy()->startOfDay();
        $exportEnd = $rangeEnd->copy()->addDay()->startOfDay();
        $result = [];

        foreach ($segments as $segment) {
            if ($segment['start']->lessThan($exportStart) || !$segment['start']->lessThan($exportEnd)) {
                continue;
            }
            if (isset($skipDays[$segment['day']])) {
                continue;
            }

            $result[$segment['month']] = ($result[$segment['month']] ?? 0) + $segment['worked_minutes'];
        }

        return $result;
    }
}
