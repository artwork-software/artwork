<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\Contracts\ActionGuard;
use Artwork\Modules\AiAssistant\Contracts\BatchRecorder;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteConflictData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteItemData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteLinkData;
use Artwork\Modules\AiAssistant\DTOs\AiWritePreviewData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteResultData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteSummaryData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\AiAssistant\Support\AiWriteIntent;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Craft\Services\CraftScopeService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Events\AssignUserToShift;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\Shift\Support\CreatedShiftsPublisher;
use Artwork\Modules\Shift\Support\SafeBroadcast;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * `POST /api/v1/ai/shift-assignments` — Werkzeug `assign_shifts` (ai-shift-planning, Core 4).
 *
 * Muster `AiEventWriteService`: `dry_run` (Vorschau), `action_id` + `args_hash` ueber den
 * `ActionGuard`, Batch fuer Undo. Unterschied: die Vorschau ist **nicht** die echte Ausfuehrung mit
 * Rollback — `assignToShift()` benachrichtigt und broadcastet. Sie ist die hypothetische Bewertung
 * aus `AiShiftPlanningService::evaluate()` (gleiche Pruefungen wie der Vorschlag), und genau diese
 * Bewertung laeuft vor der Ausfuehrung noch einmal: jeder Konflikt bricht sie ab (403 bei fehlendem
 * Recht, sonst 422 mit Grund).
 *
 * Ausfuehrung: neue Schichten anlegen (`ShiftService::save()` + Platz in der Funktion), dann
 * `ShiftWorkerService::assignToShift()` wie die Oberflaeche, dann der Regellauf wie
 * `revalidateShiftRules()`. Taucht dabei ein neuer Verstoss auf (Wettlauf mit einer anderen
 * Aenderung), wird alles zurueckgerollt (422).
 *
 * @phpstan-import-type Candidate from AiShiftPlanningService
 * @phpstan-import-type Evaluation from AiShiftPlanningService
 */
class AiShiftAssignmentWriteService
{
    public const TOOL_ASSIGN = 'assign_shifts';

    public function __construct(
        private readonly AiShiftPlanningService $planning,
        private readonly AiShiftHypothesis $hypothesis,
        private readonly CraftScopeService $craftScope,
        private readonly ShiftWorkerService $shiftWorkerService,
        private readonly ShiftService $shiftService,
        private readonly ShiftsQualificationsService $shiftsQualificationsService,
        private readonly ActionGuard $actionGuard,
        private readonly BatchRecorder $batchRecorder,
    ) {
    }

    /**
     * @param list<array{shift_id: int, shift_qualification_id: int}> $assignments
     * @param list<array{
     *     event_id: int, craft_id: int, shift_qualification_id: int, start: string, end: string,
     *     break_minutes: int|null
     * }> $newShifts
     */
    public function assign(
        User $planner,
        User|Freelancer|ServiceProvider $person,
        array $assignments,
        array $newShifts,
        AiWriteIntent $intent
    ): AiWritePreviewData|AiWriteResultData {
        $shifts = Shift::query()
            ->visibleFor($planner)
            ->whereIn('id', array_column($assignments, 'shift_id'))
            ->with(['craft', 'event', 'room'])
            ->get()
            ->keyBy('id');
        abort_if($shifts->count() !== count($assignments), Response::HTTP_NOT_FOUND);

        foreach ($newShifts as $row) {
            abort_if(Event::query()->visibleFor($planner)->find($row['event_id']) === null, Response::HTTP_NOT_FOUND);
        }

        $items = $this->previewItems($planner, $person, $assignments, $newShifts, $shifts->all());

        if ($intent->dryRun) {
            return new AiWritePreviewData(
                dry_run: true,
                tool: self::TOOL_ASSIGN,
                items: $items,
                summary: $this->summarize($items),
            );
        }

        $this->assertExecutable($planner, $intent, $items, self::TOOL_ASSIGN);

        return $this->execute($planner, $person, $assignments, $newShifts, $shifts->all(), $intent);
    }

    /**
     * Hypothetische Bewertung aller Zuweisungen zusammen (ohne Speichern).
     *
     * @param list<array{shift_id: int, shift_qualification_id: int}> $assignments
     * @param list<array<string, mixed>> $newShifts
     * @param array<int, Shift> $shifts
     * @return list<AiWriteItemData>
     */
    public function previewItems(
        User $planner,
        User|Freelancer|ServiceProvider $person,
        array $assignments,
        array $newShifts,
        array $shifts
    ): array {
        return $this->hypothesis->simulate(function () use (
            $planner,
            $person,
            $assignments,
            $newShifts,
            $shifts
        ): array {
            $access = $this->planning->craftAccess($person);
            $plannable = $this->craftScope->plannableCraftIdsFor($planner);
            $open = $this->planning->openDemand(array_keys($shifts));
            $candidates = [];

            foreach ($assignments as $assignment) {
                $shift = $shifts[$assignment['shift_id']];
                $interval = AiShiftPlanningService::intervalOf($shift) ?? [Carbon::now(), Carbon::now()];

                $candidates[] = [
                    'shift' => $shift,
                    'shift_id' => (int) $shift->id,
                    'new_shift_index' => null,
                    'qualification_id' => $assignment['shift_qualification_id'],
                    'start' => $interval[0],
                    'end' => $interval[1],
                    'pre_reasons' => $this->assignmentReasons(
                        $person,
                        $shift,
                        $assignment['shift_qualification_id'],
                        $access,
                        $plannable,
                        $open
                    ),
                ];
            }

            foreach ($this->planning->newShiftCandidates($planner, $person, $newShifts) as $candidate) {
                $candidate['pre_reasons'] = array_map(
                    static fn (array $reason): array => $reason[0] === AiShiftPlanningService::REASON_EXCLUDED
                        ? [AiWriteConflictData::MISSING_PERMISSION, $reason[1]]
                        : $reason,
                    $candidate['pre_reasons']
                );
                if (!(bool) $person->can_work_shifts) {
                    $candidate['pre_reasons'][] = [
                        AiWriteConflictData::CANNOT_WORK_SHIFTS,
                        'Diese Person kann keine Schichten uebernehmen.',
                    ];
                }
                $candidates[] = $candidate;
            }

            usort(
                $candidates,
                static fn (array $a, array $b): int => [$a['start']->getTimestamp(), $a['shift']->id]
                    <=> [$b['start']->getTimestamp(), $b['shift']->id]
            );

            [$evaluations] = $this->planning->evaluate($person, $candidates, [], false);

            return array_map(fn (array $evaluation): AiWriteItemData => $this->itemFor($evaluation), $evaluations);
        });
    }

    /**
     * @param array{crafts: list<int>, universal: bool, universal_ids: list<int>} $access
     * @param list<int>|null $plannable
     * @param array<int, array<int, int>> $open
     * @return list<array{0: string, 1: string}>
     */
    private function assignmentReasons(
        User|Freelancer|ServiceProvider $person,
        Shift $shift,
        int $qualificationId,
        array $access,
        ?array $plannable,
        array $open
    ): array {
        $reasons = [];

        if ($plannable !== null && !in_array((int) $shift->craft_id, $plannable, true)) {
            $reasons[] = [
                AiWriteConflictData::MISSING_PERMISSION,
                'Dieses Gewerk darf die planende Person nicht planen.',
            ];
        }

        if (!(bool) $person->can_work_shifts) {
            $reasons[] = [AiWriteConflictData::CANNOT_WORK_SHIFTS, 'Diese Person kann keine Schichten uebernehmen.'];
        }

        if (
            (!$access['universal'] && !in_array((int) $shift->craft_id, $access['crafts'], true))
            || !in_array(
                $qualificationId,
                $this->planning->eligibleQualificationIds($person, (int) $shift->craft_id, $access),
                true
            )
        ) {
            $reasons[] = [
                AiShiftPlanningService::REASON_NOT_QUALIFIED,
                'Die Person hat diese Funktion in diesem Gewerk nicht.',
            ];
        }

        $alreadyAssigned = ShiftWorker::query()
            ->byEmployableIdAndShiftId($person->getMorphClass(), (int) $person->getKey(), (int) $shift->id)
            ->exists();

        if ($alreadyAssigned) {
            $reasons[] = [AiWriteConflictData::ALREADY_ASSIGNED, 'Die Person ist in dieser Schicht schon eingeteilt.'];
        } elseif (($open[$shift->id][$qualificationId] ?? 0) < 1) {
            $reasons[] = [AiWriteConflictData::NO_OPEN_DEMAND, 'In dieser Funktion ist kein Platz mehr frei.'];
        }

        return $reasons;
    }

    /**
     * @param list<AiWriteItemData> $items
     */
    public function assertExecutable(User $planner, AiWriteIntent $intent, array $items, string $tool): void
    {
        $messages = [];
        $forbidden = false;

        foreach ($items as $item) {
            foreach ($item->conflicts as $conflict) {
                $forbidden = $forbidden || $conflict->type === AiWriteConflictData::MISSING_PERMISSION;
                $messages[] = $item->label . ': ' . $conflict->message;
            }
        }

        abort_if($forbidden, Response::HTTP_FORBIDDEN);

        $this->actionGuard->assertExecutable($planner, (string) $intent->actionId, $tool, $intent->args);

        abort_if(
            $messages !== [],
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Die Zuweisung verstiesse gegen Regeln: ' . implode(' | ', array_unique($messages))
        );
    }

    /**
     * @param list<array{shift_id: int, shift_qualification_id: int}> $assignments
     * @param list<array{
     *     event_id: int, craft_id: int, shift_qualification_id: int, start: string, end: string,
     *     break_minutes: int|null
     * }> $newShifts
     * @param array<int, Shift> $shifts
     */
    private function execute(
        User $planner,
        User|Freelancer|ServiceProvider $person,
        array $assignments,
        array $newShifts,
        array $shifts,
        AiWriteIntent $intent
    ): AiWriteResultData {
        DB::beginTransaction();

        try {
            $performed = $this->perform($person, $assignments, $newShifts, $shifts);
            $targets = $performed['targets'];
            $created = $performed['created'];

            $batch = $this->batchRecorder->record($planner, self::TOOL_ASSIGN, $performed['batch_items']);

            $this->actionGuard->complete($planner, (string) $intent->actionId, $batch?->id);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }

        $this->broadcast($person, $targets, $created);

        $items = array_map(fn (array $target): AiWriteItemData => $this->resultItem($target), $targets);

        return new AiWriteResultData(
            dry_run: false,
            tool: self::TOOL_ASSIGN,
            batch_id: $batch?->id,
            undo_until: $batch?->undoUntil,
            ids: array_map(static fn (array $target): int => (int) $target['shift']->id, $targets),
            items: $items,
            links: array_values(array_filter(array_map(
                static fn (array $target): ?AiWriteLinkData => $target['shift']->event_id === null ? null
                    : new AiWriteLinkData(
                        id: (int) $target['shift']->id,
                        url: route('dashboard.redirect-to-calendar', $target['shift']->event_id),
                    ),
                $targets
            ))),
            summary: $this->summarize($items),
        );
    }

    /**
     * Die Schreibarbeit einer Person — **innerhalb** einer offenen Transaktion des Aufrufers:
     * neue Schichten anlegen, zuweisen wie die Oberflaeche, Regellauf vorher/nachher (neuer
     * Verstoss → 422, der Aufrufer rollt zurueck). Liefert die Batch-Items (erst Zuweisungen, dann
     * angelegte Schichten), damit Einzel- und Sammelzuweisung denselben Weg gehen.
     *
     * @param list<array{shift_id: int, shift_qualification_id: int}> $assignments
     * @param list<array{
     *     event_id: int, craft_id: int, shift_qualification_id: int, start: string, end: string,
     *     break_minutes: int|null
     * }> $newShifts
     * @param array<int, Shift> $shifts
     * @return array{
     *     targets: list<array{shift: Shift, qualification_id: int}>,
     *     created: list<Shift>,
     *     batch_items: list<array{model_type: string, model_id: int, action: string, before_state: null}>
     * }
     */
    public function perform(
        User|Freelancer|ServiceProvider $person,
        array $assignments,
        array $newShifts,
        array $shifts
    ): array {
        /** @var list<array{shift: Shift, qualification_id: int}> $targets */
        $targets = array_map(
            static fn (array $assignment): array => [
                'shift' => $shifts[$assignment['shift_id']],
                'qualification_id' => $assignment['shift_qualification_id'],
            ],
            $assignments
        );

        $created = [];
        foreach ($newShifts as $row) {
            $shift = $this->createShift($row);
            $created[] = $shift;
            $targets[] = ['shift' => $shift, 'qualification_id' => $row['shift_qualification_id']];
        }

        [$ruleFrom, $ruleTo] = $this->ruleWindow(array_column($targets, 'shift'));
        $before = $person instanceof User ? $this->hypothesis->violations($person, $ruleFrom, $ruleTo) : [];

        $pivotIds = [];
        foreach ($targets as $target) {
            $this->shiftWorkerService->assignToShift(
                $target['shift'],
                $person,
                $target['qualification_id'],
                (string) ($target['shift']->craft?->abbreviation ?? ''),
                app(NotificationService::class),
                app(VacationConflictService::class),
                app(AvailabilityConflictService::class),
                app(ChangeService::class),
            );

            // Die Pivot-Klasse ist nicht inkrementierend — die ID steht nach dem Speichern nicht
            // am Modell, deshalb hier nachgeschlagen.
            $pivotIds[] = (int) ShiftWorker::query()
                ->byEmployableIdAndShiftId(
                    $person->getMorphClass(),
                    (int) $person->getKey(),
                    (int) $target['shift']->id
                )
                ->value('id');
        }

        if ($person instanceof User) {
            // Regellauf wie `ShiftController::assignToShift()` → `revalidateShiftRules()`; ein neuer
            // Verstoss hier heisst, dass sich seit der Pruefung etwas geaendert hat.
            $after = $this->hypothesis->violations($person, $ruleFrom, $ruleTo);
            $new = array_diff_key($after, $before);

            abort_if(
                $new !== [],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Die Zuweisung verstiesse gegen Vertragsregeln: ' . implode(', ', array_map(
                    static fn ($violation): string => (string) ($violation->shiftRule?->name
                        ?? $violation->shiftRule?->trigger_type) . ' am '
                        . $violation->violation_date?->format('d.m.Y'),
                    $new
                ))
            );
        }

        return [
            'targets' => $targets,
            'created' => $created,
            'batch_items' => array_merge(
                array_map(static fn (int $pivotId): array => [
                    'model_type' => ShiftWorker::class,
                    'model_id' => $pivotId,
                    'action' => 'create',
                    'before_state' => null,
                ], $pivotIds),
                array_map(static fn (Shift $shift): array => [
                    'model_type' => Shift::class,
                    'model_id' => (int) $shift->id,
                    'action' => 'create',
                    'before_state' => null,
                ], $created),
            ),
        ];
    }

    /**
     * @param array{
     *     event_id: int, craft_id: int, shift_qualification_id: int, start: string, end: string,
     *     break_minutes: int|null
     * } $row
     */
    private function createShift(array $row): Shift
    {
        /** @var Event $event */
        $event = Event::query()->findOrFail($row['event_id']);
        [$start, $end] = AiShiftPlanningService::newShiftInterval($row);

        $shift = new Shift([
            'event_id' => $event->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'start' => $start->format('H:i'),
            'end' => $end->format('H:i'),
            'break_minutes' => AiShiftPlanningService::breakMinutesFor($row),
            'room_id' => $event->room_id,
            'project_id' => $event->project_id,
            'event_start_day' => $start->toDateString(),
            'event_end_day' => $end->toDateString(),
            'shift_uuid' => (string) Str::uuid(),
        ]);
        $shift->craft()->associate($row['craft_id']);
        $this->shiftService->save($shift);

        $this->shiftsQualificationsService->createShiftsQualificationForShift((int) $shift->id, [
            'shift_qualification_id' => $row['shift_qualification_id'],
            'value' => 1,
        ]);

        return $shift->fresh(['craft', 'event', 'room']) ?? $shift;
    }

    /**
     * Regelfenster ueber alle Schichten, ein Tag Rand je Seite (Ruhezeit zum Vor-/Folgetag).
     *
     * @param list<Shift> $shifts
     * @return array{0: Carbon, 1: Carbon}
     */
    private function ruleWindow(array $shifts): array
    {
        $from = null;
        $to = null;

        foreach ($shifts as $shift) {
            $interval = AiShiftPlanningService::intervalOf($shift);
            if ($interval === null) {
                continue;
            }
            $from = $from === null ? $interval[0]->copy() : $from->min($interval[0]);
            $to = $to === null ? $interval[1]->copy() : $to->max($interval[1]);
        }

        $from ??= Carbon::today();
        $to ??= Carbon::today();

        return [Carbon::parse($from->toDateString())->subDay(), Carbon::parse($to->toDateString())->addDay()];
    }

    /**
     * Nach dem Commit, ueber `SafeBroadcast`: Dienstplan-Refresh je Zuweisung wie
     * `ShiftController::assignToShift()` und die neuen Schichten wie beim Anlegen in der Oberflaeche.
     *
     * @param list<array{shift: Shift, qualification_id: int}> $targets
     * @param list<Shift> $created
     */
    public function broadcast(User|Freelancer|ServiceProvider $person, array $targets, array $created): void
    {
        $entityType = match (true) {
            $person instanceof User => '0',
            $person instanceof Freelancer => '1',
            default => '2',
        };

        foreach ($targets as $target) {
            $shift = $target['shift'];
            $roomId = $shift->event_id ? $shift->event?->room_id : $shift->room_id;

            if ($roomId !== null) {
                SafeBroadcast::send(
                    new AssignUserToShift($shift, (int) $roomId, (string) $person->getKey(), $entityType)
                );
            }
        }

        if ($created !== []) {
            CreatedShiftsPublisher::publish(collect(array_map(
                static fn (Shift $shift): ?Shift => $shift->fresh(),
                $created
            ))->filter(), false);
        }
    }

    /**
     * @param Evaluation $evaluation
     */
    private function itemFor(array $evaluation): AiWriteItemData
    {
        $candidate = $evaluation['candidate'];
        $event = $candidate['shift']->event;

        return $this->item(
            $candidate['shift'],
            $candidate['shift_id'],
            $candidate['qualification_id'],
            $candidate['start'],
            $candidate['end'],
            array_map(
                static fn (array $reason): AiWriteConflictData => new AiWriteConflictData(
                    $reason[0],
                    (string) AiText::truncate($reason[1]),
                    AiRefData::make($event?->id, $event === null ? null : ($event->eventName ?: $event->name)),
                ),
                $evaluation['reasons']
            ),
        );
    }

    /**
     * @param array{shift: Shift, qualification_id: int} $target
     */
    public function resultItem(array $target): AiWriteItemData
    {
        $interval = AiShiftPlanningService::intervalOf($target['shift']);

        return $this->item(
            $target['shift'],
            (int) $target['shift']->id,
            $target['qualification_id'],
            $interval[0] ?? null,
            $interval[1] ?? null,
            [],
        );
    }

    /**
     * Beschriftung „Gewerk · Termin (Funktion)".
     *
     * @param list<AiWriteConflictData> $conflicts
     */
    private function item(
        Shift $shift,
        ?int $id,
        int $qualificationId,
        ?Carbon $start,
        ?Carbon $end,
        array $conflicts
    ): AiWriteItemData {
        $event = $shift->event;
        $qualification = ShiftQualification::query()->find($qualificationId)?->name;
        $label = implode(' · ', array_filter([
            $shift->craft?->name,
            $event === null ? null : ($event->eventName ?: $event->name),
        ])) . ($qualification ? " ({$qualification})" : '');
        $roomId = $shift->room_id ?? $event?->room_id;
        $timezone = (string) config('app.timezone');

        return new AiWriteItemData(
            action: 'create',
            id: $id,
            label: AiText::truncate($label),
            start: $start?->copy()->setTimezone($timezone)->toIso8601String(),
            end: $end?->copy()->setTimezone($timezone)->toIso8601String(),
            room: AiRefData::make($roomId === null ? null : (int) $roomId, $shift->room?->name ?? $event?->room?->name),
            conflicts: $conflicts,
        );
    }

    /**
     * @param list<AiWriteItemData> $items
     */
    public function summarize(array $items, string $tool = self::TOOL_ASSIGN): AiWriteSummaryData
    {
        $withConflicts = 0;
        $blocked = 0;

        foreach ($items as $item) {
            if ($item->conflicts === []) {
                continue;
            }

            $withConflicts++;

            foreach ($item->conflicts as $conflict) {
                if ($conflict->type === AiWriteConflictData::MISSING_PERMISSION) {
                    $blocked++;
                    break;
                }
            }
        }

        return new AiWriteSummaryData(
            action: $tool,
            total: count($items),
            with_conflicts: $withConflicts,
            blocked: $blocked,
        );
    }
}
