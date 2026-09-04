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

    /**
     * Filter des Ersatzfrei-Dashboards: craft_id, user_id, deadline_from, deadline_to (Y-m-d).
     * Der Status (offen/gewährt/überfällig) wird über die jeweilige Listenmethode abgebildet.
     *
     * @param array{craft_id?: int|null, user_id?: int|null, deadline_from?: string|null, deadline_to?: string|null} $filters
     */
    private function applyDashboardFilters(Builder $query, array $filters): Builder
    {
        $craftId = $filters['craft_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $deadlineFrom = $filters['deadline_from'] ?? null;
        $deadlineTo = $filters['deadline_to'] ?? null;

        return $query
            ->when($craftId, fn (Builder $q) => $q->whereHas('user', fn (Builder $u) => $u->whereHas('assignedCrafts', fn (Builder $c) => $c->where('crafts.id', $craftId))))
            ->when($userId, fn (Builder $q) => $q->where('user_id', $userId))
            ->when($deadlineFrom, fn (Builder $q) => $q->whereDate('deadline', '>=', $deadlineFrom))
            ->when($deadlineTo, fn (Builder $q) => $q->whereDate('deadline', '<=', $deadlineTo));
    }

    /**
     * @param int|array $filters Gewerk-ID (Altaufruf) oder Filter-Array, siehe applyDashboardFilters()
     */
    private function normalizeFilters(int|array|null $filters): array
    {
        return is_array($filters) ? $filters : ['craft_id' => $filters];
    }

    private const DASHBOARD_RELATIONS = ['user.assignedCrafts:id,name', 'violation.shiftRule'];

    public function getAllOpen(int|array|null $filters = null): Collection
    {
        return $this->applyDashboardFilters(
            CompensationDayOff::with(self::DASHBOARD_RELATIONS),
            $this->normalizeFilters($filters)
        )
            ->open()
            ->orderBy('deadline')
            ->get();
    }

    public function getAllGranted(int|array|null $filters = null): Collection
    {
        return $this->applyDashboardFilters(
            CompensationDayOff::with(array_merge(self::DASHBOARD_RELATIONS, ['grantedByUser'])),
            $this->normalizeFilters($filters)
        )
            ->granted()
            ->orderByDesc('granted_at')
            ->get();
    }

    public function getAllOverdue(int|array|null $filters = null): Collection
    {
        return $this->applyDashboardFilters(
            CompensationDayOff::with(self::DASHBOARD_RELATIONS),
            $this->normalizeFilters($filters)
        )
            ->overdue()
            ->orderBy('deadline')
            ->get();
    }

    public function getDashboardStats(int|array|null $filters = null): array
    {
        $filters = $this->normalizeFilters($filters);
        $scoped = fn () => $this->applyDashboardFilters(CompensationDayOff::query(), $filters);

        return [
            'open' => $scoped()->open()->count(),
            'granted' => $scoped()->granted()->count(),
            'overdue' => $scoped()->overdue()->count(),
            'open_value' => (float) $scoped()->open()->sum('value'),
            'granted_value' => (float) $scoped()->granted()->sum('value'),
            'overdue_value' => (float) $scoped()->overdue()->sum('value'),
        ];
    }

    /**
     * Personen, die Ersatzfrei-Einträge haben (für den Personenfilter des Dashboards).
     *
     * @return Collection<int, \Artwork\Modules\User\Models\User>
     */
    public function getUsersWithEntries(): \Illuminate\Support\Collection
    {
        $userIds = CompensationDayOff::query()->distinct()->pluck('user_id');

        return \Artwork\Modules\User\Models\User::query()
            ->whereIn('id', $userIds)
            ->select(['id', 'first_name', 'last_name'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function getGrantedHalvesForUserOnDate(int $userId, string $date): Collection
    {
        return CompensationDayOff::where('user_id', $userId)
            ->granted()
            ->where('value', '<', 1.0)
            ->whereDate('granted_date', $date)
            ->get();
    }

    /**
     * All granted half compensation days for a user within a date range, in one query.
     * Used by rule checks to avoid a per-day query (N+1) when iterating a range.
     */
    public function getGrantedHalvesForUserInRange(int $userId, string $start, string $end): Collection
    {
        return CompensationDayOff::where('user_id', $userId)
            ->granted()
            ->where('value', '<', 1.0)
            ->whereDate('granted_date', '>=', $start)
            ->whereDate('granted_date', '<=', $end)
            ->get();
    }

    public function findOpenHalfForUserExcept(int $userId, int $exceptId): ?CompensationDayOff
    {
        return CompensationDayOff::where('user_id', $userId)
            ->where('id', '!=', $exceptId)
            ->where('value', '<', 1.0)
            ->open()
            ->orderBy('deadline')
            ->first();
    }

    public function createFromProcessing(
        int $userId,
        int $violationId,
        float $totalDays,
        string $deadline,
        ?string $reason,
        bool $forHoliday = false,
        ?string $halfDayPeriod = null
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
                // The period only applies to a half day; full days keep it null.
                'half_day_period' => $value < 1.0 ? $halfDayPeriod : null,
            ]);
        }
    }
}
