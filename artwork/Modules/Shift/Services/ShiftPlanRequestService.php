<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Core\Services\HelperService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Support\SafeBroadcast;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Models\Vacation;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class ShiftPlanRequestService
{
    public function __construct(
        protected HelperService $helperService
    ) {
    }

    /**
     * Baut Marker für die Übersicht der Schichtplan-Anfrage:
     *  - "removed": Worker, die eine Schicht hatten, die nachträglich komplett gestrichen wurde, bzw.
     *    die nachträglich aus einer noch existierenden Schicht entfernt wurden → Frontend zeigt eine
     *    durchgestrichene Geister-Zelle ("nachträglich gelöscht").
     *
     * Nachträglich HINZUGEFÜGTE Schichten werden NICHT hier ermittelt, sondern im Controller als
     * echte Schicht-Zeilen geladen (Schichten im Gewerk/der KW, die nicht Teil der ursprünglichen
     * Anfrage sind) und über das Flag `is_subsequently_added` markiert.
     *
     * Quellen:
     *  - committed_shift_changes (post-commit Entfernungen, unbestätigt)
     *  - soft-gelöschte Schichten + ihre soft-gelöschten Zuordnungen (komplett gestrichene Schichten)
     *
     * @param Collection<int,Shift> $liveShifts Bereits geladene, noch existierende Schichten der Anfrage
     *        (inkl. users/freelancer/serviceProvider).
     * @return array{removed: array<int,array<string,mixed>>}
     */
    public function buildOverviewChangeMarkers(
        ShiftPlanRequest $request,
        \Carbon\Carbon $start,
        \Carbon\Carbon $end,
        Collection $liveShifts
    ): array {
        $startYmd = $start->toDateString();
        $endYmd   = $end->toDateString();

        $typeShort = [
            User::class            => 'user',
            Freelancer::class      => 'freelancer',
            ServiceProvider::class => 'service_provider',
        ];

        // Aktuell bestehende Zuordnungen als Set "${shiftId}-${type}-${id}".
        $current = [];
        foreach ($liveShifts as $shift) {
            foreach ($shift->users as $u) {
                $current[$shift->id . '-user-' . $u->id] = true;
            }
            foreach ($shift->freelancer as $f) {
                $current[$shift->id . '-freelancer-' . $f->id] = true;
            }
            foreach ($shift->serviceProvider as $s) {
                $current[$shift->id . '-service_provider-' . $s->id] = true;
            }
        }

        $removed = [];

        // 1) Post-commit Entfernungen (nachträgliche, noch unbestätigte Änderungen)
        $removedTypes = [
            'user_removed_from_shift',
            'freelancer_removed_from_shift',
            'service_provider_removed_from_shift',
        ];

        $committedChanges = CommittedShiftChange::query()
            ->where('craft_id', $request->craft_id)
            ->whereNull('acknowledged_at')
            ->whereIn('change_type', $removedTypes)
            ->get();

        foreach ($committedChanges as $change) {
            $assignment = data_get($change->field_changes, 'assignment');
            if (! is_array($assignment)) {
                continue;
            }

            $short = $typeShort[$change->affected_user_type] ?? 'user';
            $workerId = $change->affected_user_id ?? data_get($assignment, 'user_id');
            if (! $workerId) {
                continue;
            }

            $date = data_get($assignment, 'start_date'); // Y-m-d
            if (! $date || $date < $startYmd || $date > $endYmd) {
                continue;
            }

            $uniqueKey = $change->shift_id . '-' . $short . '-' . $workerId;

            // Entfernung: Geister-Zelle nur, wenn der Worker aktuell NICHT (wieder) auf der Schicht ist.
            if ($change->shift_id && isset($current[$uniqueKey])) {
                continue;
            }

            $removed[] = [
                'row_key'       => $short . '-' . $workerId,
                'type'          => $short,
                'id'            => (int) $workerId,
                'name'          => data_get($assignment, 'user_name'),
                'date'          => $date,
                'start'         => data_get($assignment, 'start_time'),
                'end'           => data_get($assignment, 'end_time'),
                'qualification' => data_get($assignment, 'shift_qualification_name'),
                'source'        => 'post_commit',
                'reason'        => 'removed',
                'shift_id'      => $change->shift_id,
            ];
        }

        // 2) Komplett gestrichene (soft-gelöschte) Schichten im Gewerk/Zeitraum inkl. ihrer Worker.
        $trashedShifts = Shift::onlyTrashed()
            ->where('craft_id', $request->craft_id)
            ->whereBetween('start_date', [$startYmd, $endYmd])
            ->get(['id', 'start_date', 'end_date', 'start', 'end']);

        if ($trashedShifts->isNotEmpty()) {
            $trashedById = $trashedShifts->keyBy('id');

            $trashedWorkers = ShiftWorker::withTrashed()
                ->whereNotNull('deleted_at')
                ->whereIn('shift_id', $trashedShifts->pluck('id')->all())
                ->get();

            $names = $this->resolveWorkerNames($trashedWorkers, $typeShort);

            foreach ($trashedWorkers as $worker) {
                $shift = $trashedById->get($worker->shift_id);
                if (! $shift) {
                    continue;
                }

                $short = $typeShort[$worker->employable_type] ?? 'user';
                $date = optional($shift->start_date)->toDateString();
                if (! $date) {
                    continue;
                }

                $removed[] = [
                    'row_key'       => $short . '-' . $worker->employable_id,
                    'type'          => $short,
                    'id'            => (int) $worker->employable_id,
                    'name'          => $names[$short . '-' . $worker->employable_id] ?? null,
                    'date'          => $date,
                    'start'         => $worker->start_time
                        ? Carbon::parse($worker->start_time)->format('H:i')
                        : (string) $shift->start,
                    'end'           => $worker->end_time
                        ? Carbon::parse($worker->end_time)->format('H:i')
                        : (string) $shift->end,
                    'qualification' => null,
                    'source'        => 'shift_deleted',
                    'reason'        => 'shift_deleted',
                    'shift_id'      => $worker->shift_id,
                ];
            }
        }

        return [
            'removed' => $removed,
        ];
    }

    /**
     * Schichten aus ANDEREN Gewerken in derselben KW für alle Personen, die mindestens einer Schicht
     * der Anfrage zugeordnet sind. Damit sieht die prüfende Person bei Personen aus "universellen"
     * Gewerken (z. B. Azubis), wie viel sie in der Woche insgesamt arbeiten. Die Schichten sind nicht
     * Teil der Anfrage und werden im Frontend nur ausgegraut angezeigt (nicht ablehnbar, kein Verlauf),
     * deshalb geht nur ein schlanker Payload raus.
     *
     * @param Collection<int,Shift> $requestShifts Bereits geladene Schichten der Anfrage
     *        (inkl. users/freelancer/serviceProvider).
     * @return array<int, array{
     *     id: int,
     *     start: ?string,
     *     end: ?string,
     *     break_minutes: int,
     *     date: string,
     *     end_date: string,
     *     is_committed: bool,
     *     room_name: ?string,
     *     craft: ?array{id: int, name: string, abbreviation: ?string, color: ?string},
     *     workers: array<int, array{type: string, id: int, shift_qualification_id: ?int}>
     * }>
     */
    public function buildForeignCraftShifts(
        ShiftPlanRequest $request,
        \Carbon\Carbon $start,
        \Carbon\Carbon $end,
        Collection $requestShifts
    ): array {
        $workerIdsByType = [
            User::class => $this->collectWorkerIds($requestShifts, 'users'),
            Freelancer::class => $this->collectWorkerIds($requestShifts, 'freelancer'),
            ServiceProvider::class => $this->collectWorkerIds($requestShifts, 'serviceProvider'),
        ];
        $workerIdsByType = array_filter($workerIdsByType, fn (array $ids) => $ids !== []);

        if ($workerIdsByType === []) {
            return [];
        }

        $typeShort = [
            User::class            => 'user',
            Freelancer::class      => 'freelancer',
            ServiceProvider::class => 'service_provider',
        ];
        $startYmd = $start->toDateString();
        $endYmd = $end->toDateString();
        $requestShiftIds = $requestShifts->pluck('id')->all();

        $assignments = ShiftWorker::query()
            ->where(function ($query) use ($workerIdsByType): void {
                foreach ($workerIdsByType as $type => $ids) {
                    $query->orWhere(fn ($q) => $q
                        ->where('employable_type', $type)
                        ->whereIn('employable_id', $ids));
                }
            })
            ->whereIn('shift_id', Shift::query()
                ->select('shifts.id')
                ->where(fn ($q) => $q
                    ->where('craft_id', '!=', $request->craft_id)
                    ->orWhereNull('craft_id'))
                ->when($requestShiftIds !== [], fn ($q) => $q->whereNotIn('shifts.id', $requestShiftIds))
                ->startAndEndDateOverlap($startYmd, $endYmd))
            ->get(['shift_id', 'employable_type', 'employable_id', 'shift_qualification_id'])
            ->groupBy('shift_id');

        if ($assignments->isEmpty()) {
            return [];
        }

        return Shift::query()
            ->whereIn('id', $assignments->keys())
            ->with([
                'craft:id,name,abbreviation,color',
                'room:id,name',
                'event:id,room_id',
                'event.room:id,name',
            ])
            ->orderBy('start_date')
            ->orderBy('start')
            ->get()
            ->map(fn (Shift $shift) => [
                'id' => $shift->id,
                'start' => $shift->start,
                'end' => $shift->end,
                'break_minutes' => (int) $shift->break_minutes,
                'date' => Carbon::parse($shift->start_date)->toDateString(),
                'end_date' => Carbon::parse($shift->end_date)->toDateString(),
                'is_committed' => (bool) $shift->is_committed,
                'room_name' => $shift->room?->name ?? $shift->event?->room?->name,
                'craft' => $shift->craft ? [
                    'id' => $shift->craft->id,
                    'name' => $shift->craft->name,
                    'abbreviation' => $shift->craft->abbreviation,
                    'color' => $shift->craft->color,
                ] : null,
                'workers' => $assignments->get($shift->id)
                    ->map(fn (ShiftWorker $worker) => [
                        'type' => $typeShort[$worker->employable_type],
                        'id' => (int) $worker->employable_id,
                        'shift_qualification_id' => $worker->shift_qualification_id,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Abwesenheiten (alles außer „Verfügbar“) der Personen einer Anfrage in deren KW, dazu die
     * Zuweisungen der Anfrage, die mit einer Abwesenheit kollidieren (gleiche Regel wie das
     * Warndreieck im Dienstplan, ShiftWorkerAvailability).
     *
     * @param Collection<int,Shift> $requestShifts Bereits geladene Schichten der Anfrage
     *        (inkl. users/freelancer/serviceProvider mit Pivot).
     * @return array{
     *     absences: array<int, array{
     *         id: int, worker_type: string, worker_id: int, date: string, type: string,
     *         full_day: bool, day_part: ?string, start_time: ?string, end_time: ?string, comment: ?string
     *     }>,
     *     unavailable_assignments: array<string, string>
     * }
     */
    public function buildWorkerAbsences(
        ShiftPlanRequest $request,
        \Carbon\Carbon $start,
        \Carbon\Carbon $end,
        Collection $requestShifts
    ): array {
        $typeShort = [
            User::class            => 'user',
            Freelancer::class      => 'freelancer',
            ServiceProvider::class => 'service_provider',
        ];
        $relationByType = [
            User::class            => 'users',
            Freelancer::class      => 'freelancer',
            ServiceProvider::class => 'serviceProvider',
        ];

        $workerIdsByType = $this->requestWorkerIdsByType($request, $start, $end);
        foreach ($relationByType as $type => $relation) {
            $workerIdsByType[$type] = array_values(array_unique(array_merge(
                $workerIdsByType[$type] ?? [],
                $this->collectWorkerIds($requestShifts, $relation)
            )));
        }
        $workerIdsByType = array_filter($workerIdsByType, fn (array $ids) => $ids !== []);

        if ($workerIdsByType === []) {
            return ['absences' => [], 'unavailable_assignments' => []];
        }

        $vacations = Vacation::query()
            ->without(['series', 'conflicts'])
            ->where(function ($query) use ($workerIdsByType): void {
                foreach ($workerIdsByType as $type => $ids) {
                    $query->orWhere(fn ($q) => $q
                        ->where('vacationer_type', $type)
                        ->whereIn('vacationer_id', $ids));
                }
            })
            ->where('type', '!=', VacationType::AVAILABLE->value)
            // Je einen Tag davor und danach: Schichten über Mitternacht reichen in den Nachbartag (z. B. So → Mo)
            ->whereBetween('date', [$start->copy()->subDay()->toDateString(), $end->copy()->addDay()->toDateString()])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $startYmd = $start->toDateString();
        $endYmd = $end->toDateString();
        $absences = $vacations
            ->filter(function (Vacation $vacation) use ($startYmd, $endYmd): bool {
                $day = Carbon::parse($vacation->date)->toDateString();

                return $day >= $startYmd && $day <= $endYmd;
            })
            ->map(fn (Vacation $vacation) => [
                'id' => $vacation->id,
                'worker_type' => $typeShort[$vacation->vacationer_type] ?? 'user',
                'worker_id' => (int) $vacation->vacationer_id,
                'date' => Carbon::parse($vacation->date)->toDateString(),
                'type' => $vacation->type instanceof VacationType ? $vacation->type->value : (string) $vacation->type,
                'full_day' => (bool) $vacation->full_day,
                'day_part' => $vacation->day_part,
                'start_time' => $vacation->full_day ? null : $vacation->start_time?->format('H:i'),
                'end_time' => $vacation->full_day ? null : $vacation->end_time?->format('H:i'),
                'comment' => $vacation->comment !== null && trim($vacation->comment) !== ''
                    ? $vacation->comment
                    : null,
            ])
            ->values()
            ->all();

        $vacationsByWorker = $vacations->groupBy(
            fn (Vacation $vacation) => $vacation->vacationer_type . '-' . $vacation->vacationer_id
        );

        $unavailableAssignments = [];
        foreach ($requestShifts as $shift) {
            foreach ($relationByType as $type => $relation) {
                if (!$shift->relationLoaded($relation)) {
                    continue;
                }
                foreach ($shift->getRelation($relation) as $worker) {
                    $workerVacations = $vacationsByWorker->get($type . '-' . $worker->id);
                    if ($workerVacations === null) {
                        continue;
                    }
                    // Relation nur für die Prüfung setzen, damit sie nicht im Schicht-Payload landet
                    $worker->setRelation('vacations', $workerVacations);
                    $status = ShiftWorkerAvailability::getWorkerUnavailableStatus($shift, $worker);
                    $worker->unsetRelation('vacations');

                    if ($status !== null) {
                        $unavailableAssignments[$shift->id . '-' . $typeShort[$type] . '-' . $worker->id] = $status;
                    }
                }
            }
        }

        return ['absences' => $absences, 'unavailable_assignments' => $unavailableAssignments];
    }

    /**
     * @param Collection<int,Shift> $shifts
     * @return array<int,int>
     */
    protected function collectWorkerIds(Collection $shifts, string $relation): array
    {
        return $shifts
            ->flatMap(fn (Shift $shift) => $shift->relationLoaded($relation)
                ? $shift->getRelation($relation)->pluck('id')
                : [])
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Löst die Anzeigenamen für (soft-gelöschte) ShiftWorker auf, gruppiert nach Typ.
     *
     * @param Collection<int,ShiftWorker> $workers
     * @param array<class-string,string> $typeShort
     * @return array<string,string> key: "${short}-${id}" => Name
     */
    protected function resolveWorkerNames(Collection $workers, array $typeShort): array
    {
        $names = [];

        $byType = $workers->groupBy('employable_type');
        foreach ($byType as $type => $group) {
            $short = $typeShort[$type] ?? null;
            if (! $short || ! class_exists($type)) {
                continue;
            }

            $ids = $group->pluck('employable_id')->unique()->all();
            /** @var Collection<int,\Illuminate\Database\Eloquent\Model> $models */
            $models = $type::query()->whereIn('id', $ids)->get();

            foreach ($models as $model) {
                $name = $model->full_name
                    ?? ($model->name ?? trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? '')));
                $names[$short . '-' . $model->id] = $name !== '' ? $name : null;
            }
        }

        return $names;
    }

    /**
     * Create a ShiftPlanRequest and attach the provided shifts into the historical pivot table.
     *
     * @param array $data  // attributes for ShiftPlanRequest
     *                     (craft_id, week_number, year, status, requested_by_user_id...)
     * @param array<int> $shiftIds
     * @param bool $withSnapshot // if true, store a small snapshot per shift
     * @return ShiftPlanRequest
     */
    public function createRequestWithShifts(array $data, array $shiftIds, bool $withSnapshot = true): ShiftPlanRequest
    {
        // Create the request
        /** @var ShiftPlanRequest $request */
        $request = ShiftPlanRequest::create(Arr::only($data, [
            'craft_id',
            'week_number',
            'year',
            'status',
            'requested_by_user_id',
            'requested_at',
            'reviewed_by_user_id',
            'reviewed_at',
            'review_comment',
        ]));

        if (empty($shiftIds)) {
            return $request;
        }

        $shifts = Shift::whereIn('id', $shiftIds)->get();

        $attachPayload = [];
        foreach ($shifts as $shift) {
            $payload = [];

            if ($withSnapshot) {
                $payload['snapshot'] = json_encode([
                    'start_date' => $shift->start_date?->toDateString() ?? null,
                    'end_date' => $shift->end_date?->toDateString() ?? null,
                    'start' => (string)$shift->start,
                    'end' => (string)$shift->end,
                    'craft_id' => $shift->craft_id,
                    'shift_id' => $shift->id,
                ], JSON_THROW_ON_ERROR);
            }

            $attachPayload[$shift->id] = $payload;
        }

        $request->requestedShifts()->attach($attachPayload);

        return $request;
    }

    /**
     * Attach shifts to an existing ShiftPlanRequest (idempotent).
     * Uses syncWithoutDetaching to avoid duplicate entries.
     *
     * @param ShiftPlanRequest $request
     * @param array<int>|Collection<int,Shift> $shifts Shift-IDs oder bereits geladene Modelle
     *        (erspart den erneuten Load inkl. des automatischen $with von Shift)
     * @param bool $withSnapshot
     * @return void
     */
    public function attachShiftsToRequest(
        ShiftPlanRequest $request,
        array|Collection $shifts,
        bool $withSnapshot = true
    ): void {
        if (is_array($shifts)) {
            if (empty($shifts)) {
                return;
            }

            $shifts = Shift::whereIn('id', $shifts)->get();
        }

        if ($shifts->isEmpty()) {
            return;
        }

        $attachPayload = [];
        foreach ($shifts as $shift) {
            $payload = [];

            if ($withSnapshot) {
                $payload['snapshot'] = json_encode([
                    'start_date' => $shift->start_date?->toDateString() ?? null,
                    'end_date' => $shift->end_date?->toDateString() ?? null,
                    'start' => (string)$shift->start,
                    'end' => (string)$shift->end,
                    'craft_id' => $shift->craft_id,
                    'shift_id' => $shift->id,
                ], JSON_THROW_ON_ERROR);
            }

            $attachPayload[$shift->id] = $payload;
        }

        // idempotent attach
        $request->requestedShifts()->syncWithoutDetaching($attachPayload);
    }

    /**
     * Hängt alle aktuell "freien" Schichten des Gewerks/der KW an eine bestehende Anfrage an
     * (Activity-Log, Pivot-Historie, Workflow-Flags) – exakt die Logik hinter
     * "Alle Schichten festsetzen". Wird sowohl vom Controller (store) als auch vom
     * Backfill-Command genutzt, damit es nur EINE Quelle der Wahrheit gibt.
     *
     * Freie Schichten = keine Zuordnung, verwaiste Zuordnung oder Zuordnung zu einer bereits
     * abgeschlossenen (rejected/approved) Anfrage. Bereits in einer aktiven (pending) Anfrage
     * befindliche Schichten werden NICHT erneut angehängt.
     *
     * @return array<int> Die IDs der neu angehängten Schichten.
     */
    /**
     * Query der aktuell "freien" Schichten für das Gewerk/die KW einer Anfrage.
     * Freie Schicht = keine Zuordnung, verwaiste Zuordnung oder Zuordnung zu einer bereits
     * abgeschlossenen (rejected/approved) Anfrage. Source of Truth ist der Anfrage-Status,
     * NICHT das denormalisierte `in_workflow`-Flag.
     */
    public function freeShiftsForRequestQuery(ShiftPlanRequest $request): \Illuminate\Database\Eloquent\Builder
    {
        [$start, $end] = $this->helperService->getDateRangeByCalendarWeekAndYear(
            $request->week_number,
            $request->year
        );

        return Shift::query()
            ->where('craft_id', $request->craft_id)
            ->startAndEndDateOverlap($start->toDateString(), $end->toDateString())
            ->where(function ($q): void {
                $q
                    ->whereNull('current_request_id')
                    ->orWhereDoesntHave('currentRequest')
                    ->orWhereHas('currentRequest', function ($sub): void {
                        $sub->whereIn('status', ['rejected', 'approved']);
                    });
            });
    }

    /**
     * Personen, deren individuelle Zeiten zu einer Anfrage gehören können: Mitglieder des Gewerks
     * plus alle, die in der KW auf einer Schicht des Gewerks eingeteilt sind (auch aus anderen
     * Gewerken ausgeliehene Personen erscheinen in der Prüfansicht als eigene Zeile).
     *
     * @return array<class-string, array<int, int>>
     */
    public function requestWorkerIdsByType(
        ShiftPlanRequest $request,
        \Carbon\Carbon $start,
        \Carbon\Carbon $end
    ): array {
        $craft = $request->craft()->first();

        $idsByType = [
            User::class => $craft?->users()->pluck('users.id')->all() ?? [],
            Freelancer::class => $craft?->freelancers()->pluck('freelancers.id')->all() ?? [],
            ServiceProvider::class => $craft?->serviceProviders()->pluck('service_providers.id')->all() ?? [],
        ];

        $assigned = ShiftWorker::query()
            ->whereIn('shift_id', Shift::query()
                ->select('shifts.id')
                ->where('craft_id', $request->craft_id)
                ->startAndEndDateOverlap($start->toDateString(), $end->toDateString()))
            ->get(['employable_type', 'employable_id']);

        foreach ($assigned as $worker) {
            if (array_key_exists($worker->employable_type, $idsByType)) {
                $idsByType[$worker->employable_type][] = (int) $worker->employable_id;
            }
        }

        return array_map(
            fn (array $ids) => array_values(array_unique(array_map('intval', $ids))),
            $idsByType
        );
    }

    /**
     * Individuelle Zeiten der Personen einer Anfrage in deren KW (alle, unabhängig vom Freigabe-Status).
     *
     * @param array<class-string, array<int, int>> $workerIdsByType
     * @return \Illuminate\Database\Eloquent\Builder<IndividualTime>
     */
    public function individualTimesOfWorkersQuery(
        array $workerIdsByType,
        \Carbon\Carbon $start,
        \Carbon\Carbon $end
    ): \Illuminate\Database\Eloquent\Builder {
        $workerIdsByType = array_filter($workerIdsByType, fn (array $ids) => $ids !== []);

        return IndividualTime::query()
            ->individualByDateRange($start->toDateString(), $end->toDateString())
            ->where(function ($query) use ($workerIdsByType): void {
                if ($workerIdsByType === []) {
                    $query->whereRaw('1 = 0');
                }
                foreach ($workerIdsByType as $type => $ids) {
                    $query->orWhere(fn ($q) => $q
                        ->where('timeable_type', $type)
                        ->whereIn('timeable_id', $ids));
                }
            });
    }

    /**
     * Individuelle Zeiten, die eine Anfrage aufnehmen darf: noch nicht freigegeben und nicht in einer
     * offenen Anfrage (auch nicht in der eines anderen Gewerks – jede Zeit gehört genau einer Anfrage).
     * Zeiten aus abgelehnten Anfragen sind wieder frei.
     *
     * @return \Illuminate\Database\Eloquent\Builder<IndividualTime>
     */
    public function freeIndividualTimesForRequestQuery(ShiftPlanRequest $request): \Illuminate\Database\Eloquent\Builder
    {
        [$start, $end] = $this->helperService->getDateRangeByCalendarWeekAndYear(
            $request->week_number,
            $request->year
        );

        return $this->individualTimesOfWorkersQuery($this->requestWorkerIdsByType($request, $start, $end), $start, $end)
            ->where('is_committed', false)
            ->where(function ($query): void {
                $query
                    ->whereNull('current_request_id')
                    ->orWhereDoesntHave('currentRequest')
                    ->orWhereHas('currentRequest', fn ($sub) => $sub->whereIn('status', ['rejected', 'approved']));
            });
    }

    /**
     * Hängt die freien individuellen Zeiten an die Anfrage (Verlauf + Workflow-Flags).
     *
     * @return array<int, int> IDs der angehängten Zeiten
     */
    public function attachFreeIndividualTimesToRequest(ShiftPlanRequest $request): array
    {
        $ids = $this->freeIndividualTimesForRequestQuery($request)->pluck('id')->all();

        if ($ids === []) {
            return [];
        }

        $request->requestedIndividualTimes()->syncWithoutDetaching($ids);

        IndividualTime::query()->whereIn('id', $ids)->update([
            'current_request_id' => $request->id,
            'in_workflow' => true,
        ]);

        return $ids;
    }

    /**
     * Genehmigung: alle individuellen Zeiten der Anfrage freigeben. Der Live-Zeiger wird wie bei
     * Schichten gelöst, die Zugehörigkeit bleibt im Verlauf (requestedIndividualTimes).
     */
    public function commitIndividualTimesOfRequest(ShiftPlanRequest $request, ?User $causer): int
    {
        return IndividualTime::query()
            ->where('current_request_id', $request->id)
            ->update([
                'is_committed' => true,
                'in_workflow' => false,
                'current_request_id' => null,
                'committing_user_id' => $causer?->id,
            ]);
    }

    /**
     * Ablehnung: Zeiten verlassen den Workflow. Der Zeiger auf die abgelehnte Anfrage bleibt (wie bei
     * Schichten); über deren Status gelten die Zeiten für die nächste Anfrage wieder als frei.
     */
    public function releaseIndividualTimesOfRejectedRequest(ShiftPlanRequest $request): int
    {
        return IndividualTime::query()
            ->where('current_request_id', $request->id)
            ->update(['in_workflow' => false]);
    }

    /**
     * Zurückziehen: Zeiten verlassen den Workflow, Zeiger und Verlauf der gelöschten Anfrage entfallen.
     */
    public function releaseIndividualTimesOfWithdrawnRequest(ShiftPlanRequest $request): int
    {
        $released = IndividualTime::query()
            ->where('current_request_id', $request->id)
            ->update(['in_workflow' => false, 'current_request_id' => null]);

        $request->requestedIndividualTimes()->detach();

        return $released;
    }

    public function attachFreeShiftsToRequest(
        ShiftPlanRequest $request,
        ?User $causer = null,
        bool $broadcast = true
    ): array {
        $shiftsQuery = $this->freeShiftsForRequestQuery($request);

        $shifts = $shiftsQuery
            // currentRequest für den History-Log, event:id,room_id für die Raum-Ermittlung
            // beim Broadcast (spart pro Schicht einen Lazy-Load des kompletten Events).
            ->with(['currentRequest', 'event:id,room_id'])
            ->get();

        $shiftIdsToAttach = [];

        foreach ($shifts as $shift) {
            $previousRequest = null;
            if ($shift->currentRequest && $shift->currentRequest->status === 'rejected') {
                $previousRequest = $shift->currentRequest;
            }

            $activity = activity()
                ->performedOn($shift)
                ->useLog('shift')
                ->event('shift_added_to_request')
                ->withProperties([
                    'old' => [
                        'in_workflow' => $shift->in_workflow,
                    ],
                    'attributes' => [
                        'in_workflow' => true,
                    ],
                    'shift_plan_request_id' => $request->id,
                    'previous_shift_plan_request_id' => $previousRequest?->id,
                ]);

            if ($causer) {
                $activity->causedBy($causer);
            }

            if ($previousRequest) {
                $activity->tap(function (Activity $activity) use ($previousRequest, $request): void {
                    $props = $activity->properties ?? collect();

                    $props = $props->merge([
                        'translation_key' => 'Shift rejected in request from {0} with reason "{1}", now added to {2}',
                        'translation_key_placeholder_values' => [
                            optional($previousRequest->created_at)->format('d.m.Y'),
                            $previousRequest->rejection_reason ?? '',
                            optional($request->created_at)->format('d.m.Y'),
                        ],
                    ]);

                    $activity->properties = $props;
                });
            }

            $activity->log('Shift added to shift plan request');

            $shiftIdsToAttach[] = $shift->id;
        }

        if (! empty($shiftIdsToAttach)) {
            // Pivot-Historie (idempotent) — die bereits geladenen Modelle weiterreichen.
            $this->attachShiftsToRequest($request, $shifts, true);

            $shiftsQuery->update([
                'current_request_id' => $request->id,
                'in_workflow' => true,
                'workflow_rejection_reason' => null,
            ]);

            DB::table('shift_workers')
                ->whereIn('shift_id', $shiftIdsToAttach)
                ->update(['workflow_rejection_reason' => null]);
        }

        if ($broadcast && $shifts->isNotEmpty()) {
            // Das Bulk-Update oben feuert keine Model-Events — die geladenen Modelle
            // in-memory angleichen statt pro Schicht fresh() (je 1 Query) zu ziehen.
            foreach ($shifts as $shift) {
                $shift->current_request_id = $request->id;
                $shift->in_workflow = true;
                $shift->workflow_rejection_reason = null;
                $shift->syncOriginal();
            }

            // UpdateShiftInShiftPlan ist ShouldBroadcastNow: jeder broadcast() ist ein
            // synchroner HTTP-Call an Reverb + Relation-Loads für das DTO. Bei einer
            // vollen KW blockiert das den Request um Sekunden — deshalb erst nach dem
            // Response ausführen (läuft im selben Prozess, braucht keinen Queue-Worker).
            dispatch(function () use ($shifts): void {
                foreach ($shifts as $shift) {
                    $roomId = $shift->room_id ?? $shift->event?->room_id;
                    if ($roomId !== null) {
                        SafeBroadcast::send(new UpdateShiftInShiftPlan($shift, $roomId));
                    }
                }
            })->afterResponse();
        }

        return $shiftIdsToAttach;
    }
}
