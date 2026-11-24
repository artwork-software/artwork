<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiPeriodData;
use Artwork\Modules\AiAssistant\DTOs\AiPersonRefData;
use Artwork\Modules\AiAssistant\DTOs\AiPersonShiftData;
use Artwork\Modules\AiAssistant\DTOs\AiPersonShiftListData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Services\ShiftAssignmentPreflightService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Alle Schichten einer Person im Zeitraum (core-ai-aggregates, Entgrenzen P1) — fuer Fragen wie
 * „Schichten von X im letzten Monat" und deren Export in einem Aufruf.
 *
 * Zeiten wie im eigenen Einsatzplan: individuelle Zeiten am Pivot schlagen die Schichtzeiten
 * (`ShiftAssignmentPreflightService::resolvePivotInterval()`). Minuten = Dauer minus Pause, die Pause
 * einmal je Schicht wie in `WorkTimeCalculationService::shiftMinutesPerDay()`.
 */
class AiPersonShiftsService
{
    public function __construct(private readonly AiShiftReadService $shiftRead)
    {
    }

    public function shifts(
        User $viewer,
        User|Freelancer|ServiceProvider $person,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $limit
    ): AiPersonShiftListData {
        $relation = match (true) {
            $person instanceof User => 'users',
            $person instanceof Freelancer => 'freelancer',
            default => 'serviceProvider',
        };
        $table = $person->getTable();

        $query = Shift::query()
            ->visibleFor($viewer)
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereRaw('COALESCE(end_date, start_date) >= ?', [$from->toDateString()])
            ->whereHas($relation, fn (Builder $builder) => $builder->where("{$table}.id", $person->id));

        // Der eigene Einsatzplan blendet nicht festgeschriebene Schichten je nach Hauseinstellung aus.
        $own = $person instanceof User && $person->id === $viewer->id;
        if ($own && $this->shiftRead->hidesUncommittedShifts($viewer)) {
            $query->where('is_committed', true);
        }

        /** @var Collection<int, Shift> $all */
        $all = $query
            ->with([
                'craft:id,name',
                'room:id,name',
                'project:id,name',
                'event' => fn ($event) => $event->with('room:id,name'),
                $relation => fn ($worker) => $worker->where("{$table}.id", $person->id),
            ])
            ->orderBy('start_date')
            ->orderBy('start')
            ->get();

        $qualifications = ShiftQualification::query()->pluck('name', 'id')->all();
        $entries = $all->map(fn (Shift $shift): AiPersonShiftData => $this->entry(
            $shift,
            $shift->getRelation($relation)->first()?->pivot,
            $qualifications
        ));

        return new AiPersonShiftListData(
            person: new AiPersonRefData(
                AiPeopleService::typeOf($person),
                $person->id,
                AiPeopleService::nameOf($person)
            ),
            period: AiPeriodData::fromDates($from, $to),
            shifts: $entries->take($limit)->values()->all(),
            total: $entries->count(),
            total_minutes: (int) $entries->sum('minutes'),
            truncated: $entries->count() > $limit,
        );
    }

    /**
     * @param array<int, string> $qualifications
     */
    private function entry(Shift $shift, mixed $pivot, array $qualifications): AiPersonShiftData
    {
        $interval = $pivot instanceof ShiftWorker
            ? ShiftAssignmentPreflightService::resolvePivotInterval($pivot, $shift)
            : null;
        $break = max(0, (int) ($shift->break_minutes ?? 0));
        $minutes = $interval === null ? 0 : max(0, (int) $interval[0]->diffInMinutes($interval[1]) - $break);
        $event = $shift->event;
        $room = $shift->room ?? $event?->room;
        $qualificationId = $pivot instanceof ShiftWorker ? (int) $pivot->shift_qualification_id : null;

        return new AiPersonShiftData(
            shift_id: $shift->id,
            start: $this->iso($interval[0] ?? null),
            end: $this->iso($interval[1] ?? null),
            minutes: $minutes,
            break_minutes: $break,
            craft: AiRefData::make($shift->craft_id, $shift->craft?->name),
            qualification: $qualificationId === null || !isset($qualifications[$qualificationId])
                ? null
                : AiRefData::make($qualificationId, (string) $qualifications[$qualificationId]),
            event: AiRefData::make($shift->event_id, $event === null ? null : ($event->eventName ?: $event->name)),
            project: AiRefData::make($shift->project_id, $shift->project?->name),
            room: AiRefData::make($room?->id, $room?->name),
            committed: (bool) $shift->is_committed,
        );
    }

    private function iso(?Carbon $moment): ?string
    {
        return $moment?->copy()->setTimezone((string) config('app.timezone'))->toIso8601String();
    }
}
