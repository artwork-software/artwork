<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ShiftRuleViolationRepository extends BaseRepository
{
    public function getNewModelInstance(): ShiftRuleViolation
    {
        return new ShiftRuleViolation();
    }

    public function getNewModelQuery(): Builder
    {
        return ShiftRuleViolation::query();
    }

    public function getActiveWithRelations(): Collection
    {
        return ShiftRuleViolation::with(['shiftRule', 'user', 'shift', 'createdByUser'])
            ->where('status', 'active')
            ->orderBy('violation_date', 'desc')
            ->get();
    }

    /**
     * Relationen der Verstoßliste/-exporte: nur die Spalten, die Anzeige und Export lesen.
     *
     * @return array<int|string, mixed>
     */
    public static function listRelations(): array
    {
        return [
            'shiftRule:id,name,description,trigger_type,warning_color,default_compensation_days,default_compensation_deadline_days',
            'user:id,first_name,last_name',
            'user.assignedCrafts:id,name,abbreviation',
            'shift:id,start_date,end_date,start,end,room_id,craft_id',
            'shift.room:id,name',
            'shift.craft:id,name,abbreviation',
            'createdByUser:id,first_name,last_name',
            'resolvedByUser:id,first_name,last_name',
            'compensationDayOffs:id,violation_id,granted_at,granted_date',
        ];
    }

    /**
     * Gefilterte Verstoß-Query (Liste "Offene Verstöße" und Excel-Export teilen sich die Filter).
     *
     * @param array{
     *     craft_ids?: array<int, int>|null,
     *     user_id?: int|null,
     *     shift_rule_id?: int|null,
     *     severity?: string|null,
     *     status?: string|array<int, string>|null,
     *     date_from?: string|null,
     *     date_to?: string|null
     * } $filters
     */
    public function filteredQuery(array $filters): Builder
    {
        $status = $filters['status'] ?? 'active';
        $craftIds = array_values(array_filter(array_map('intval', (array) ($filters['craft_ids'] ?? []))));

        return ShiftRuleViolation::query()
            ->when(
                $status !== null && $status !== 'all' && $status !== [],
                fn (Builder $q) => $q->whereIn('status', (array) $status)
            )
            ->when(
                $craftIds !== [],
                fn (Builder $q) => $q->whereHas(
                    'user.assignedCrafts',
                    fn (Builder $c) => $c->whereIn('crafts.id', $craftIds)
                )
            )
            ->when(!empty($filters['user_id']), fn (Builder $q) => $q->where('user_id', (int) $filters['user_id']))
            ->when(
                !empty($filters['shift_rule_id']),
                fn (Builder $q) => $q->where('shift_rule_id', (int) $filters['shift_rule_id'])
            )
            ->when(!empty($filters['severity']), fn (Builder $q) => $q->where('severity', $filters['severity']))
            ->when(!empty($filters['date_from']), fn (Builder $q) => $q->whereDate('violation_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn (Builder $q) => $q->whereDate('violation_date', '<=', $filters['date_to']));
    }

    /**
     * Seite der gefilterten Verstöße (Datum auf-/absteigend, danach ID absteigend für stabile Seiten).
     */
    public function paginateFiltered(array $filters, int $perPage, string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $direction = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $this->filteredQuery($filters)
            ->with(self::listRelations())
            ->orderBy('violation_date', $direction)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Zähler-Chips: aktive Verstöße gesamt / Fehler / Warnungen — mit allen Filtern außer Status.
     *
     * @return array{total: int, error: int, warning: int}
     */
    public function countActiveBySeverity(array $filters): array
    {
        $rows = $this->filteredQuery(array_merge($filters, ['status' => 'active']))
            ->selectRaw('severity, COUNT(*) AS aggregate')
            ->groupBy('severity')
            ->pluck('aggregate', 'severity');

        $error = (int) ($rows['error'] ?? 0);
        $warning = (int) ($rows['warning'] ?? 0);

        return [
            'total' => (int) $rows->sum(),
            'error' => $error,
            'warning' => $warning,
        ];
    }

    /**
     * Personen mit Verstößen (Personenfilter der Liste) — nur ID und Name.
     */
    public function getUsersWithViolations(): \Illuminate\Support\Collection
    {
        $userIds = ShiftRuleViolation::query()->distinct()->pluck('user_id');

        return \Artwork\Modules\User\Models\User::query()
            ->whereIn('id', $userIds)
            ->select(['id', 'first_name', 'last_name'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Sammelaktion "Ignorieren": dieselbe Ignore-Logik wie je Verstoß (Status, Zeitpunkt, Person, Grund,
     * Activity-Log), in EINER Transaktion; nur aktive Verstöße werden angefasst. Liefert die Anzahl.
     *
     * @param array<int, int> $ids
     */
    public function ignoreMany(array $ids, ?int $userId, ?string $ignoreReason): int
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return 0;
        }

        return DB::transaction(function () use ($ids, $userId, $ignoreReason): int {
            $violations = ShiftRuleViolation::query()
                ->whereIn('id', $ids)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            foreach ($violations as $violation) {
                $this->ignore($violation, $userId, $ignoreReason);
            }

            return $violations->count();
        });
    }

    public function getActiveForDateRange(string $startDate, string $endDate, ?array $userIds = null): Collection
    {
        $query = ShiftRuleViolation::with(['shiftRule:id,name,description,warning_color,default_compensation_days,default_compensation_deadline_days'])
            ->whereBetween('violation_date', [$startDate, $endDate])
            ->where('status', 'active');

        if (!empty($userIds)) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->get();
    }

    public function createViolation(array $attributes): ShiftRuleViolation
    {
        return ShiftRuleViolation::create($attributes);
    }

    public function resolve(ShiftRuleViolation $violation, ?int $userId = null): void
    {
        $this->update($violation, [
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }

    public function ignore(ShiftRuleViolation $violation, ?int $userId = null, ?string $ignoreReason = null): void
    {
        $this->update($violation, [
            'status' => 'ignored',
            'resolved_at' => now(),
            'resolved_by' => $userId,
            'ignore_reason' => $ignoreReason,
        ]);
    }

    public function getUnprocessedViolationsForUser(int $userId): Collection
    {
        return ShiftRuleViolation::with(['shiftRule'])
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereNull('compensation_days')
            ->orderByDesc('violation_date')
            ->get();
    }
}
