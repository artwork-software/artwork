<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CompensationDayOffRepository extends BaseRepository
{
    public function getNewModelInstance(): CompensationDayOff
    {
        return new CompensationDayOff();
    }

    public function getNewModelQuery(): Builder
    {
        return CompensationDayOff::query();
    }

    public function getOpenForUser(int $userId): Collection
    {
        return CompensationDayOff::with(['violation.shiftRule'])
            ->where('user_id', $userId)
            ->open()
            ->orderBy('deadline')
            ->get();
    }

    public function getGrantedForUser(int $userId): Collection
    {
        return CompensationDayOff::with(['violation.shiftRule', 'grantedByUser'])
            ->where('user_id', $userId)
            ->granted()
            ->orderByDesc('granted_at')
            ->get();
    }

    public function getGrantedForDateRange(array $userIds, string $start, string $end): Collection
    {
        return CompensationDayOff::whereIn('user_id', $userIds)
            ->whereNotNull('granted_date')
            ->whereBetween('granted_date', [$start, $end])
            ->with([
                'violation:id,shift_rule_id',
                'violation.shiftRule:id,name',
                'grantedByUser:id,first_name,last_name',
            ])
            ->get();
    }

    public function getAllOpen(?int $craftId = null): Collection
    {
        return CompensationDayOff::with(['user', 'violation.shiftRule'])
            ->when($craftId, fn (Builder $q) => $q->whereHas('user', fn (Builder $u) => $u->whereHas('assignedCrafts', fn (Builder $c) => $c->where('crafts.id', $craftId))))
            ->open()
            ->orderBy('deadline')
            ->get();
    }

    public function getAllGranted(?int $craftId = null): Collection
    {
        return CompensationDayOff::with(['user', 'violation.shiftRule', 'grantedByUser'])
            ->when($craftId, fn (Builder $q) => $q->whereHas('user', fn (Builder $u) => $u->whereHas('assignedCrafts', fn (Builder $c) => $c->where('crafts.id', $craftId))))
            ->granted()
            ->orderByDesc('granted_at')
            ->get();
    }

    public function getAllOverdue(?int $craftId = null): Collection
    {
        return CompensationDayOff::with(['user', 'violation.shiftRule'])
            ->when($craftId, fn (Builder $q) => $q->whereHas('user', fn (Builder $u) => $u->whereHas('assignedCrafts', fn (Builder $c) => $c->where('crafts.id', $craftId))))
            ->overdue()
            ->orderBy('deadline')
            ->get();
    }

    public function getDashboardStats(?int $craftId = null): array
    {
        $craftScope = fn (Builder $q) => $q->whereHas('user', fn (Builder $u) => $u->whereHas('assignedCrafts', fn (Builder $c) => $c->where('crafts.id', $craftId)));

        return [
            'open' => CompensationDayOff::open()->when($craftId, $craftScope)->count(),
            'granted' => CompensationDayOff::granted()->when($craftId, $craftScope)->count(),
            'overdue' => CompensationDayOff::overdue()->when($craftId, $craftScope)->count(),
            'open_value' => (float) CompensationDayOff::open()->when($craftId, $craftScope)->sum('value'),
            'granted_value' => (float) CompensationDayOff::granted()->when($craftId, $craftScope)->sum('value'),
            'overdue_value' => (float) CompensationDayOff::overdue()->when($craftId, $craftScope)->sum('value'),
        ];
    }

    public function createFromProcessing(
        int $userId,
        int $violationId,
        float $totalDays,
        string $deadline,
        ?string $reason,
        bool $forHoliday = false
    ): void {
        $records = [];

        while ($totalDays >= 1.0) {
            $records[] = 1.0;
            $totalDays -= 1.0;
        }
        if ($totalDays >= 0.5) {
            $records[] = 0.5;
        }

        foreach ($records as $value) {
            CompensationDayOff::create([
                'user_id' => $userId,
                'violation_id' => $violationId,
                'value' => $value,
                'deadline' => $deadline,
                'reason' => $reason,
                'for_holiday' => $forHoliday,
            ]);
        }
    }
}
