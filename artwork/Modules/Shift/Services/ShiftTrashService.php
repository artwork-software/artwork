<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Craft\Services\CraftScopeService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Support\CreatedShiftsPublisher;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\WorkingHourCacheService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Papierkorb für eigenständige Schichten (ohne Termin — Termin-Schichten kommen mit ihrem Termin
 * zurück). Sichtbar und bearbeitbar sind nur Schichten der Gewerke, die die Person planen darf.
 */
class ShiftTrashService
{
    /**
     * Schichtplätze/Zuweisungen gelten als "mit der Schicht gelöscht", wenn sie höchstens so lange vor
     * der Schicht gelöscht wurden (ShiftDeletionService löscht sie im selben Request). Früher einzeln
     * entfernte Plätze bleiben so beim Wiederherstellen weg.
     */
    private const CASCADE_WINDOW_SECONDS = 60;

    public function __construct(
        private readonly ShiftService $shiftService,
        private readonly ShiftRuleService $shiftRuleService,
        private readonly CraftScopeService $craftScopeService,
        private readonly WorkingHourCacheService $workingHourCacheService,
    ) {
    }

    public function trashedQuery(User $user): Builder
    {
        $plannableCraftIds = $this->craftScopeService->plannableCraftIdsFor($user);

        return Shift::onlyTrashed()
            ->whereNull('event_id')
            ->when($plannableCraftIds !== null, fn (Builder $query) => $query->whereIn('craft_id', $plannableCraftIds));
    }

    public function paginate(User $user, string $search, int $perPage): LengthAwarePaginator
    {
        return $this->trashedQuery($user)
            ->with([
                'craft:id,name,abbreviation,color',
                'room' => static fn ($query) => $query->withTrashed()->select(['id', 'name', 'deleted_at']),
                'project' => static fn ($query) => $query->withTrashed()->select(['id', 'name', 'deleted_at']),
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%' . $search . '%';
                $query->where(function (Builder $subQuery) use ($like): void {
                    $subQuery->where('description', 'like', $like)
                        ->orWhereHas('craft', fn (Builder $craft) => $craft
                            ->where('name', 'like', $like)
                            ->orWhere('abbreviation', 'like', $like))
                        ->orWhereHas('room', fn (Builder $room) => $room->withTrashed()->where('name', 'like', $like))
                        ->orWhereHas(
                            'project',
                            fn (Builder $project) => $project->withTrashed()->where('name', 'like', $like)
                        );
                });
            })
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Shift $shift): array => $this->present($shift));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Shift $shift): array
    {
        $startDate = Carbon::parse($shift->start_date);
        $endDate = Carbon::parse($shift->end_date ?? $shift->start_date);

        return [
            'id' => $shift->id,
            'date' => $startDate->isSameDay($endDate)
                ? $startDate->format('d.m.Y')
                : $startDate->format('d.m.Y') . ' – ' . $endDate->format('d.m.Y'),
            'time' => substr((string) $shift->start, 0, 5) . ' – ' . substr((string) $shift->end, 0, 5),
            'craft' => $shift->craft ? [
                'name' => $shift->craft->name,
                'abbreviation' => $shift->craft->abbreviation,
                'color' => $shift->craft->color,
            ] : null,
            'room_name' => $shift->room?->name,
            'room_trashed' => $shift->room === null || $shift->room->trashed(),
            'project_name' => $shift->project?->name,
            'description' => $shift->description,
            'is_committed' => (bool) $shift->is_committed,
            'worker_count' => $this->deletedWithShift(ShiftWorker::onlyTrashed(), $shift)->count(),
            'deleted_at' => $shift->deleted_at?->format('d.m.Y, H:i'),
        ];
    }

    /**
     * Schicht mit Schichtplätzen und Besetzung zurückholen und wie eine neu geplante Schicht bewerten
     * (Projekt-Tageszuordnungen, Regeln, bei festgeschriebenen Schichten Urlaubs-/Verfügbarkeits-
     * konflikte); offene Dienstplan-Ansichten zeigen sie sofort wieder an.
     *
     * @throws RuntimeException wenn der Raum fehlt oder selbst im Papierkorb liegt
     */
    public function restore(User $user, Shift $shift): void
    {
        $this->craftScopeService->assertCanPlanShifts($user, [$shift]);

        $room = $shift->room()->withTrashed()->first();
        if ($room === null || $room->trashed()) {
            throw new RuntimeException(__(
                'The room of this shift is in the recycle bin or no longer exists. Restore the room first.'
            ));
        }

        DB::transaction(function () use ($shift): void {
            $this->deletedWithShift(ShiftsQualifications::onlyTrashed(), $shift)
                ->get()
                ->each(static fn (ShiftsQualifications $slot) => $slot->restore());
            $this->deletedWithShift(ShiftWorker::onlyTrashed(), $shift)
                ->get()
                ->each(static fn (ShiftWorker $worker) => $worker->restore());
            $shift->restore();

            $this->shiftService->resyncProjectDayAssignments($shift);
        });

        $this->workingHourCacheService->forgetForShift($shift);

        $start = Carbon::parse($shift->start_date);
        $end = Carbon::parse($shift->end_date ?? $shift->start_date);
        foreach ($shift->users()->get() as $worker) {
            $this->shiftRuleService->validateRulesForUser($worker, $start->copy(), $end->copy());
        }

        if ($shift->is_committed) {
            $this->shiftService->recheckAvailabilityConflicts($shift);
        }

        CreatedShiftsPublisher::publish(collect([$shift->fresh()]), false);
    }

    public function forceDelete(User $user, Shift $shift): void
    {
        $this->craftScopeService->assertCanPlanShifts($user, [$shift]);
        $this->shiftService->forceDelete($shift);
    }

    /**
     * Alle Schichten im Papierkorb endgültig löschen, die die Person sieht (planbare Gewerke).
     */
    public function forceDeleteAll(User $user): int
    {
        $count = 0;
        $this->trashedQuery($user)->chunkById(200, function ($shifts) use (&$count): void {
            foreach ($shifts as $shift) {
                $this->shiftService->forceDelete($shift);
                $count++;
            }
        });

        return $count;
    }

    private function deletedWithShift(Builder $query, Shift $shift): Builder
    {
        return $query
            ->where('shift_id', $shift->id)
            ->where('deleted_at', '>=', $shift->deleted_at->copy()->subSeconds(self::CASCADE_WINDOW_SECONDS));
    }
}
