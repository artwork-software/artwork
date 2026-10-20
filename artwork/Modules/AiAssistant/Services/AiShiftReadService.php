<?php

namespace Artwork\Modules\AiAssistant\Services;

use App\Settings\ShiftSettings;
use Artwork\Modules\AiAssistant\DTOs\AiAbsenceData;
use Artwork\Modules\AiAssistant\DTOs\AiAbsenceListData;
use Artwork\Modules\AiAssistant\DTOs\AiAvailabilityData;
use Artwork\Modules\AiAssistant\DTOs\AiAvailabilityListData;
use Artwork\Modules\AiAssistant\DTOs\AiEventShiftAssigneeData;
use Artwork\Modules\AiAssistant\DTOs\AiEventShiftData;
use Artwork\Modules\AiAssistant\DTOs\AiEventShiftQualificationData;
use Artwork\Modules\AiAssistant\DTOs\AiEventShiftsData;
use Artwork\Modules\AiAssistant\DTOs\AiEventShiftsEventData;
use Artwork\Modules\AiAssistant\DTOs\AiEventShiftsSummaryData;
use Artwork\Modules\AiAssistant\DTOs\AiPeriodData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiScheduleData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftListData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftPlanAssigneeData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftPlanData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftPlanQualificationData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftPlanShiftData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Services\ShiftAssignmentPreflightService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Eigener Einsatzplan und Dienstplan-Ausschnitt fuer artwork-ai
 * (core-api-schedule-shifts; Konzept B1 §3, §6).
 *
 * Gelesen wird ueber `Shift::visibleFor($user)`. Der Einsatzplan ist zusaetzlich auf die eigenen
 * Zuweisungen eingeschraenkt — er hat bewusst keinen Parameter fuer fremde Personen (B1 §6).
 */
class AiShiftReadService
{
    /** @var array<int, string>|null Funktionsnamen, einmal je Abfrage geladen. */
    private ?array $qualificationNames = null;

    public function __construct(private readonly ShiftSettings $shiftSettings)
    {
    }

    public function schedule(User $user, CarbonImmutable $from, CarbonImmutable $to, ?int $limit): AiScheduleData
    {
        $limit = $this->limit($limit);

        return new AiScheduleData(
            period: AiPeriodData::fromDates($from, $to),
            shifts: $this->ownShifts($user, $from, $to, $limit),
            availabilities: $this->ownAvailabilities($user, $from, $to, $limit),
            absences: $this->ownAbsences($user, $from, $to, $limit),
        );
    }

    public function shiftPlan(
        User $user,
        CarbonImmutable $weekStart,
        CarbonImmutable $weekEnd,
        string $week,
        ?int $craftId,
        ?int $limit
    ): AiShiftPlanData {
        $limit = $this->limit($limit);

        $query = $this->shiftsInRange(Shift::query()->visibleFor($user), $weekStart, $weekEnd)
            ->when($craftId !== null, fn (Builder $builder) => $builder->where('craft_id', $craftId));

        $total = (clone $query)->count();

        /** @var Collection<int, Shift> $shifts */
        $shifts = $query
            ->with(['craft', 'users', 'freelancer', 'serviceProvider'])
            ->orderBy('start_date')
            ->orderBy('start')
            ->limit($limit)
            ->get();

        $staffing = $this->staffingByShift($shifts->modelKeys());
        $craft = $craftId === null ? null : Craft::query()->find($craftId);

        return new AiShiftPlanData(
            week: $week,
            period: AiPeriodData::fromDates($weekStart, $weekEnd),
            craft: $craft === null ? null : AiRefData::make($craft->id, $craft->name),
            data: $shifts->map(
                fn (Shift $shift): AiShiftPlanShiftData => $this->shiftPlanEntry($shift, $staffing[$shift->id] ?? [])
            )->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    /**
     * Schichten eines Termins mit Besetzung (ai-shift-planning, Erweiterung 2). Gelesen wird ueber
     * `Shift::visibleFor()`; der Aufrufer hat „Schichtplan einsehen" bereits geprueft.
     */
    public function eventShifts(User $user, Event $event): AiEventShiftsData
    {
        /** @var Collection<int, Shift> $shifts */
        $shifts = Shift::query()
            ->visibleFor($user)
            ->where('event_id', $event->id)
            ->with(['craft', 'users', 'freelancer', 'serviceProvider'])
            ->orderBy('start_date')
            ->orderBy('start')
            ->get();

        $staffing = $this->staffingByShift($shifts->modelKeys());
        $names = $this->qualificationNames();
        $entries = [];
        $totals = ['required' => 0, 'staffed' => 0, 'open' => 0];

        foreach ($shifts as $shift) {
            $interval = ShiftAssignmentPreflightService::resolveInterval(
                $shift->start_date,
                $shift->start,
                $shift->end_date ?: $shift->start_date,
                $shift->end,
            );
            $qualifications = [];

            foreach ($staffing[$shift->id] ?? [] as $qualificationId => $counts) {
                $open = max(0, $counts['required'] - $counts['staffed']);
                $totals['required'] += $counts['required'];
                $totals['staffed'] += min($counts['staffed'], $counts['required']);
                $totals['open'] += $open;

                $qualifications[] = new AiEventShiftQualificationData(
                    id: $qualificationId,
                    name: AiText::truncate($names[$qualificationId] ?? null),
                    required: $counts['required'],
                    staffed: $counts['staffed'],
                    open: $open,
                );
            }

            $entries[] = new AiEventShiftData(
                id: $shift->id,
                craft: AiRefData::make($shift->craft_id, $shift->craft?->name),
                start: $this->iso($interval[0] ?? null),
                end: $this->iso($interval[1] ?? null),
                committed: (bool) $shift->is_committed,
                qualifications: $qualifications,
                assignees: array_map(
                    static fn (AiShiftPlanAssigneeData $assignee): AiEventShiftAssigneeData
                        => new AiEventShiftAssigneeData(
                            name: $assignee->display_name,
                            qualification: $assignee->qualification,
                        ),
                    $this->assignees($shift, $names)
                ),
            );
        }

        $timezone = (string) config('app.timezone');

        return new AiEventShiftsData(
            event: new AiEventShiftsEventData(
                id: $event->id,
                name: AiText::truncate($event->eventName ?: $event->name),
                start: $event->start_time?->copy()->setTimezone($timezone)->toIso8601String(),
                end: $event->end_time?->copy()->setTimezone($timezone)->toIso8601String(),
                room: AiRefData::make($event->room_id, $event->room?->name),
                project: AiRefData::make($event->project_id, $event->project?->name),
            ),
            shifts: $entries,
            summary: new AiEventShiftsSummaryData(
                shifts: count($entries),
                required: $totals['required'],
                staffed: $totals['staffed'],
                open: $totals['open'],
                fully_staffed: $totals['open'] === 0,
            ),
        );
    }

    private function ownShifts(User $user, CarbonImmutable $from, CarbonImmutable $to, int $limit): AiShiftListData
    {
        $query = $this->shiftsInRange(Shift::query()->visibleFor($user), $from, $to)
            ->whereHas('users', fn (Builder $builder) => $builder->where('users.id', $user->id));

        if ($this->hidesUncommittedShifts($user)) {
            $query->where('is_committed', true);
        }

        $total = (clone $query)->count();

        /** @var Collection<int, Shift> $shifts */
        $shifts = $query
            ->with([
                'craft',
                'event',
                'project',
                'users' => fn ($relation) => $relation->where('users.id', $user->id),
            ])
            ->orderBy('start_date')
            ->orderBy('start')
            ->limit($limit)
            ->get();

        return new AiShiftListData(
            data: $shifts->map($this->ownShiftEntry(...))->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    /**
     * Eine eigene Zuweisung. Zeiten kommen aus dem Pivot, wenn dort individuelle Zeiten stehen
     * (ShiftAssignmentPreflightService::resolvePivotInterval()).
     */
    private function ownShiftEntry(Shift $shift): AiShiftData
    {
        /** @var ShiftWorker|null $pivot */
        $pivot = $shift->users->first()?->pivot;
        $interval = $pivot === null
            ? null
            : ShiftAssignmentPreflightService::resolvePivotInterval($pivot, $shift);
        $event = $shift->event;

        return new AiShiftData(
            id: $shift->id,
            start: $this->iso($interval[0] ?? null),
            end: $this->iso($interval[1] ?? null),
            craft: AiRefData::make($shift->craft_id, $shift->craft?->name),
            qualification: $this->qualificationFor($pivot),
            event: AiRefData::make($shift->event_id, $event === null ? null : ($event->eventName ?: $event->name)),
            project: AiRefData::make($shift->project_id, $shift->project?->name),
            committed: (bool) $shift->is_committed,
        );
    }

    private function qualificationFor(?ShiftWorker $pivot): ?string
    {
        if ($pivot === null) {
            return null;
        }

        return $this->qualificationNames()[(int) $pivot->shift_qualification_id] ?? null;
    }

    private function ownAvailabilities(
        User $user,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $limit
    ): AiAvailabilityListData {
        $query = Availability::query()
            ->where('available_type', User::class)
            ->where('available_id', $user->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

        $total = (clone $query)->count();
        $entries = $query->orderBy('date')->limit($limit)->get();

        return new AiAvailabilityListData(
            data: $entries->map(AiAvailabilityData::fromModel(...))->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    private function ownAbsences(
        User $user,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $limit
    ): AiAbsenceListData {
        $query = Vacation::query()
            ->where('vacationer_type', User::class)
            ->where('vacationer_id', $user->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

        $total = (clone $query)->count();
        $entries = $query->orderBy('date')->limit($limit)->get();

        return new AiAbsenceListData(
            data: $entries->map(AiAbsenceData::fromModel(...))->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    /**
     * Schichten, die in den Zeitraum ragen. `COALESCE(end_date, start_date)` wie in der
     * Zuweisungssuche: eintaegige Altbestaende haben kein Enddatum.
     *
     * @param Builder<Shift> $builder
     * @return Builder<Shift>
     */
    private function shiftsInRange(Builder $builder, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return $builder
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereRaw('COALESCE(end_date, start_date) >= ?', [$from->toDateString()]);
    }

    /**
     * Spiegelt `UserService::shouldHideUncommittedShiftsInOwnRoster()` fuer den Token-User.
     *
     * Die Methode selbst ist unter `auth:api` nicht benutzbar: sie liest den Web-Guard und
     * `can()`, das ohne den Guard `web` fuer jedes Hausrecht still `false` liefert.
     */
    private function hidesUncommittedShifts(User $user): bool
    {
        if (!$this->shiftSettings->hide_uncommitted_shifts_from_own_roster) {
            return false;
        }

        return !$user->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            && !$user->checkPermissionTo(PermissionEnum::CAN_VIEW_OWN_UNCOMMITTED_SHIFTS->value, 'web');
    }

    /**
     * Bedarf und regulaere Besetzung je Schicht und Funktion — dieselbe Rechnung wie
     * `ShiftWeekStatusService::applyStaffing()`: ueberbuchte Zuweisungen zaehlen nicht mit.
     *
     * @param list<int> $shiftIds
     * @return array<int, array<int, array{required: int, staffed: int}>>
     */
    private function staffingByShift(array $shiftIds): array
    {
        if ($shiftIds === []) {
            return [];
        }

        $assigned = [];

        $workers = DB::table('shift_workers')
            ->select(['shift_id', 'shift_qualification_id', 'is_overbooked'])
            ->whereIn('shift_id', $shiftIds)
            ->whereNull('deleted_at')
            ->get();

        foreach ($workers as $worker) {
            if ($worker->is_overbooked) {
                continue;
            }

            $shiftId = (int) $worker->shift_id;
            $qualificationId = (int) $worker->shift_qualification_id;
            $assigned[$shiftId][$qualificationId] = ($assigned[$shiftId][$qualificationId] ?? 0) + 1;
        }

        $staffing = [];

        $requirements = ShiftsQualifications::query()
            ->select(['shift_id', 'shift_qualification_id', 'value'])
            ->whereIn('shift_id', $shiftIds)
            ->get();

        foreach ($requirements as $requirement) {
            $value = (int) $requirement->value;

            if ($value <= 0) {
                continue;
            }

            $shiftId = (int) $requirement->shift_id;
            $qualificationId = (int) $requirement->shift_qualification_id;

            $staffing[$shiftId][$qualificationId] = [
                'required' => $value,
                'staffed' => $assigned[$shiftId][$qualificationId] ?? 0,
            ];
        }

        return $staffing;
    }

    /**
     * @param array<int, array{required: int, staffed: int}> $staffing
     */
    private function shiftPlanEntry(Shift $shift, array $staffing): AiShiftPlanShiftData
    {
        $interval = ShiftAssignmentPreflightService::resolveInterval(
            $shift->start_date,
            $shift->start,
            $shift->end_date ?: $shift->start_date,
            $shift->end,
        );

        $names = $this->qualificationNames();
        $qualifications = [];
        $required = 0;
        $staffed = 0;

        foreach ($staffing as $qualificationId => $counts) {
            $required += $counts['required'];
            $staffed += $counts['staffed'];

            $qualifications[] = new AiShiftPlanQualificationData(
                qualification: AiRefData::make($qualificationId, $names[$qualificationId] ?? null),
                required: $counts['required'],
                staffed: $counts['staffed'],
                open: max(0, $counts['required'] - $counts['staffed']),
            );
        }

        return new AiShiftPlanShiftData(
            id: $shift->id,
            start: $this->iso($interval[0] ?? null),
            end: $this->iso($interval[1] ?? null),
            craft: AiRefData::make($shift->craft_id, $shift->craft?->name),
            required: $required,
            staffed: $staffed,
            open: max(0, $required - $staffed),
            committed: (bool) $shift->is_committed,
            qualifications: $qualifications,
            assigned: $this->assignees($shift, $names),
        );
    }

    /**
     * Eingeteilte Personen mit Anzeigenamen — Nutzer, Freelancer und Dienstleister, wie im
     * Dienstplan. Mehr als der Name geht nicht raus (Brief B1c).
     *
     * @param array<int, string> $qualificationNames
     * @return list<AiShiftPlanAssigneeData>
     */
    private function assignees(Shift $shift, array $qualificationNames): array
    {
        $assignees = [];

        foreach ([$shift->users, $shift->freelancer, $shift->serviceProvider] as $workers) {
            foreach ($workers as $worker) {
                /** @var ShiftWorker|null $pivot */
                $pivot = $worker->pivot;
                $name = $this->displayName($worker);

                if ($name === '') {
                    continue;
                }

                $assignees[] = new AiShiftPlanAssigneeData(
                    display_name: (string) AiText::truncate($name),
                    qualification: $qualificationNames[(int) $pivot?->shift_qualification_id] ?? null,
                    overbooked: (bool) $pivot?->is_overbooked,
                );
            }
        }

        return $assignees;
    }

    private function displayName(Model $worker): string
    {
        if ($worker instanceof User) {
            return trim($worker->getFullNameAttribute());
        }

        if ($worker instanceof Freelancer) {
            return trim($worker->getNameAttribute());
        }

        if ($worker instanceof ServiceProvider) {
            return trim($worker->getNameAttribute());
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    private function qualificationNames(): array
    {
        if ($this->qualificationNames === null) {
            /** @var array<int, string> $names */
            $names = ShiftQualification::query()
                ->pluck('name', 'id')
                ->map(static fn (mixed $name): string => (string) $name)
                ->all();

            $this->qualificationNames = $names;
        }

        return $this->qualificationNames;
    }

    private function iso(?Carbon $moment): ?string
    {
        return $moment?->copy()->setTimezone((string) config('app.timezone'))->toIso8601String();
    }

    private function limit(?int $requested): int
    {
        $max = (int) config('ai.list_limit');

        if ($requested === null || $requested < 1) {
            return $max;
        }

        return min($requested, $max);
    }
}
