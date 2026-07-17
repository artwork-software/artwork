<?php

namespace Artwork\Modules\WorkTime\Services;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CraftDistributionExportService
{
    /**
     * Distribution of shift hours per craft for every worker assigned to a universally applicable craft.
     * Shift minutes in crafts outside the selection are collected in the "other" bucket.
     *
     * @param array<int> $craftIds empty array analyzes all crafts
     * @return array{
     *     universalCraft: array{id: int, name: string},
     *     crafts: Collection<int, array{id: int, name: string}>,
     *     rows: Collection<int, array{name: string, minutes: array<int, int>, other_minutes: int, total_minutes: int}>,
     *     total: array{minutes: array<int, int>, other_minutes: int, total_minutes: int}
     * }
     */
    public function buildDistribution(
        Carbon $rangeStart,
        Carbon $rangeEnd,
        int $universalCraftId,
        array $craftIds
    ): array {
        $rangeStart = $rangeStart->copy()->startOfDay();
        $rangeEnd = $rangeEnd->copy()->endOfDay();

        $universalCraft = Craft::query()
            ->without(['craftShiftPlaner', 'craftInventoryPlaner'])
            ->with([
                'users' => fn ($query) => $query
                    ->without('shiftQualifications')
                    ->select(['users.id', 'users.first_name', 'users.last_name']),
                'freelancers' => fn ($query) => $query
                    ->without('shiftQualifications')
                    ->select(['freelancers.id', 'freelancers.first_name', 'freelancers.last_name']),
                'serviceProviders' => fn ($query) => $query
                    ->without('shiftQualifications')
                    ->select(['service_providers.id', 'service_providers.provider_name']),
            ])
            ->findOrFail($universalCraftId);

        $crafts = Craft::query()
            ->without(['craftShiftPlaner', 'craftInventoryPlaner'])
            ->when($craftIds !== [], fn ($query) => $query->whereIn('id', $craftIds))
            ->orderBy('position')
            ->get(['id', 'name', 'position']);
        $selectedCraftIds = $crafts->pluck('id')->all();

        $minutesByType = [
            User::class => $this->shiftMinutesByWorkerAndCraft(
                User::class,
                $universalCraft->users->pluck('id')->unique()->all(),
                $rangeStart,
                $rangeEnd,
            ),
            Freelancer::class => $this->shiftMinutesByWorkerAndCraft(
                Freelancer::class,
                $universalCraft->freelancers->pluck('id')->unique()->all(),
                $rangeStart,
                $rangeEnd,
            ),
            ServiceProvider::class => $this->shiftMinutesByWorkerAndCraft(
                ServiceProvider::class,
                $universalCraft->serviceProviders->pluck('id')->unique()->all(),
                $rangeStart,
                $rangeEnd,
            ),
        ];

        $rows = collect();

        foreach ($universalCraft->users->unique('id') as $user) {
            $rows->push($this->workerRow(
                trim($user->first_name . ' ' . $user->last_name),
                $minutesByType[User::class][$user->id] ?? [],
                $selectedCraftIds,
            ));
        }
        foreach ($universalCraft->freelancers->unique('id') as $freelancer) {
            $rows->push($this->workerRow(
                trim($freelancer->first_name . ' ' . $freelancer->last_name),
                $minutesByType[Freelancer::class][$freelancer->id] ?? [],
                $selectedCraftIds,
            ));
        }
        foreach ($universalCraft->serviceProviders->unique('id') as $serviceProvider) {
            $rows->push($this->workerRow(
                (string) $serviceProvider->provider_name,
                $minutesByType[ServiceProvider::class][$serviceProvider->id] ?? [],
                $selectedCraftIds,
            ));
        }

        $rows = $rows->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return [
            'universalCraft' => ['id' => $universalCraft->id, 'name' => $universalCraft->name],
            'crafts' => $crafts->map(fn (Craft $craft) => ['id' => $craft->id, 'name' => $craft->name])->values(),
            'rows' => $rows,
            'total' => $this->totalRow($rows, $selectedCraftIds),
        ];
    }

    /**
     * @param array<int, int> $minutesByCraft [craftId] => minutes
     * @param array<int> $selectedCraftIds
     * @return array{name: string, minutes: array<int, int>, other_minutes: int, total_minutes: int}
     */
    private function workerRow(string $name, array $minutesByCraft, array $selectedCraftIds): array
    {
        $minutes = [];
        foreach ($selectedCraftIds as $craftId) {
            $minutes[$craftId] = $minutesByCraft[$craftId] ?? 0;
        }

        $otherMinutes = array_sum(
            array_filter(
                $minutesByCraft,
                fn (int $craftId) => !in_array($craftId, $selectedCraftIds, true),
                ARRAY_FILTER_USE_KEY,
            ),
        );

        return [
            'name' => $name,
            'minutes' => $minutes,
            'other_minutes' => $otherMinutes,
            'total_minutes' => array_sum($minutes) + $otherMinutes,
        ];
    }

    /**
     * @param Collection<
     *     int,
     *     array{name: string, minutes: array<int, int>, other_minutes: int, total_minutes: int}
     * > $rows
     * @param array<int> $selectedCraftIds
     * @return array{minutes: array<int, int>, other_minutes: int, total_minutes: int}
     */
    private function totalRow(Collection $rows, array $selectedCraftIds): array
    {
        $minutes = array_fill_keys($selectedCraftIds, 0);
        $otherMinutes = 0;
        $totalMinutes = 0;

        foreach ($rows as $row) {
            foreach ($row['minutes'] as $craftId => $craftMinutes) {
                $minutes[$craftId] += $craftMinutes;
            }
            $otherMinutes += $row['other_minutes'];
            $totalMinutes += $row['total_minutes'];
        }

        return [
            'minutes' => $minutes,
            'other_minutes' => $otherMinutes,
            'total_minutes' => $totalMinutes,
        ];
    }

    /**
     * @param class-string<User|Freelancer|ServiceProvider> $employableType
     * @param array<int> $workerIds
     * @return array<int, array<int, int>> [workerId][craftId] => minutes
     */
    private function shiftMinutesByWorkerAndCraft(
        string $employableType,
        array $workerIds,
        Carbon $rangeStart,
        Carbon $rangeEnd
    ): array {
        if ($workerIds === []) {
            return [];
        }

        $minutesByWorker = [];
        $rangeEndExclusive = $rangeEnd->copy()->startOfDay()->addDay();

        ShiftWorker::query()
            ->where('employable_type', $employableType)
            ->whereIn('employable_id', $workerIds)
            ->where('start_date', '<=', $rangeEnd->toDateString())
            ->where('end_date', '>=', $rangeStart->toDateString())
            ->with('shift:id,craft_id,break_minutes')
            ->get()
            ->each(function (ShiftWorker $worker) use (
                &$minutesByWorker,
                $employableType,
                $rangeEndExclusive,
                $rangeStart,
            ): void {
                $minutes = $this->workedMinutesWithinRange($worker, $rangeStart, $rangeEndExclusive);
                if ($minutes === null) {
                    return;
                }

                // shift_count is a headcount only for service providers
                if ($employableType === ServiceProvider::class) {
                    $minutes *= max(1, (int) ($worker->shift_count ?? 1));
                }

                $workerId = (int) $worker->employable_id;
                $craftId = (int) $worker->shift->craft_id;
                $minutesByWorker[$workerId][$craftId] = ($minutesByWorker[$workerId][$craftId] ?? 0) + $minutes;
            });

        return $minutesByWorker;
    }

    private function workedMinutesWithinRange(
        ShiftWorker $worker,
        Carbon $rangeStart,
        Carbon $rangeEndExclusive,
    ): ?int {
        if (
            !$worker->start_date ||
            !$worker->end_date ||
            !$worker->start_time ||
            !$worker->end_time ||
            !$worker->shift
        ) {
            return null;
        }

        $start = $worker->start_date->copy()->setTimeFromTimeString((string) $worker->start_time);
        $end = $worker->end_date->copy()->setTimeFromTimeString((string) $worker->end_time);
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $segmentStart = $start->greaterThan($rangeStart) ? $start : $rangeStart;
        $segmentEnd = $end->lessThan($rangeEndExclusive) ? $end : $rangeEndExclusive;
        if ($segmentEnd->lessThanOrEqualTo($segmentStart)) {
            return null;
        }

        $totalMinutes = (int) $start->diffInMinutes($end);
        if ($totalMinutes <= 0) {
            return null;
        }

        $segmentMinutes = (int) $segmentStart->diffInMinutes($segmentEnd);
        $workedMinutes = max(0, $totalMinutes - max(0, (int) ($worker->shift->break_minutes ?? 0)));

        return intdiv($segmentMinutes * $workedMinutes, $totalMinutes);
    }
}
