<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\Contracts\ActionGuard;
use Artwork\Modules\AiAssistant\Contracts\BatchRecorder;
use Artwork\Modules\AiAssistant\DTOs\AiPersonRefData;
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
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Events\RemoveEntityFormShiftEvent;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Services\ShiftReplacementService;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\Shift\Support\SafeBroadcast;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * `POST /api/v1/ai/shift-assignments/replace` — Werkzeug `replace_in_shifts` (ai-scenarios-4 S9).
 *
 * Je Zeile wird `from` aus der Schicht genommen und `to` eingetragen — in **einer** Transaktion ueber
 * `ShiftReplacementService::replace()`, denselben Weg wie „Ersetzen" im Dienstplan (Austragen ueber
 * `removeFromShift()`, Eintragen ueber `assignToShift()`, Benachrichtigungen, Verlauf).
 *
 * Regeln hart: die Vorschau ist die hypothetische Bewertung von `assign_shifts`
 * (`AiShiftAssignmentWriteService::previewItems()`) in einer Simulation, in der die Zuweisungen von
 * `from` schon entfernt sind — so ist der Platz frei und `to` wird ueber alle seine Zeilen zusammen
 * geprueft. Die Ausfuehrung prueft erneut; jeder Konflikt ausser Hinweisen bricht ab (403 bei
 * fehlendem Recht, sonst 422), ein nach dem Eintragen neu auftretender Regelverstoss rollt alles
 * zurueck (422).
 *
 * `mark_absent` legt fuer `from` an den Tagen der Schichten ganztaegige Abwesenheiten an
 * (`AiAbsenceWriteService`, Typ wie S11). Undo: erst die Abwesenheiten loeschen, dann `to` austragen,
 * dann `from` aus dem gespeicherten Pivot wieder eintragen.
 */
class AiShiftReplaceWriteService
{
    public const TOOL_REPLACE = 'replace_in_shifts';

    private const HINTS = [
        AiWriteConflictData::HAS_SHIFTS,
        AiWriteConflictData::ALREADY_ABSENT,
    ];

    public function __construct(
        private readonly AiPeopleService $people,
        private readonly AiShiftAssignmentWriteService $single,
        private readonly AiShiftHypothesis $hypothesis,
        private readonly AiAbsenceWriteService $absences,
        private readonly ShiftReplacementService $replacementService,
        private readonly ShiftRuleService $shiftRuleService,
        private readonly ActionGuard $actionGuard,
        private readonly BatchRecorder $batchRecorder,
    ) {
    }

    /**
     * @param list<array{
     *     shift_id: int, shift_qualification_id: int,
     *     from: array{type: string, id: int}, to: array{type: string, id: int}
     * }> $rows
     */
    public function replace(
        User $planner,
        array $rows,
        ?string $markAbsent,
        AiWriteIntent $intent
    ): AiWritePreviewData|AiWriteResultData {
        $shiftIds = array_values(array_unique(array_column($rows, 'shift_id')));
        $shifts = Shift::query()
            ->visibleFor($planner)
            ->whereIn('id', $shiftIds)
            ->with(['craft', 'event', 'room'])
            ->get()
            ->keyBy('id');
        abort_if($shifts->count() !== count($shiftIds), Response::HTTP_NOT_FOUND);

        $resolved = [];
        foreach ($rows as $row) {
            $from = $this->people->find($row['from']['type'], $row['from']['id']);
            $resolved[] = $row + [
                'from_person' => $from,
                'to_person' => $this->people->find($row['to']['type'], $row['to']['id']),
                'pivot' => ShiftWorker::query()
                    ->byEmployableIdAndShiftId($from->getMorphClass(), (int) $from->getKey(), $row['shift_id'])
                    ->first(),
            ];
        }

        $absentDays = $markAbsent === null ? [] : $this->absentDays($resolved, $shifts->all());
        $items = $this->previewItems($planner, $resolved, $shifts->all(), $markAbsent, $absentDays);

        if ($intent->dryRun) {
            return new AiWritePreviewData(true, self::TOOL_REPLACE, $items, $this->summarize($items));
        }

        $this->assertExecutable($planner, $intent, $items);

        DB::beginTransaction();

        try {
            $batchItems = $this->perform($planner, $resolved, $shifts->all(), $markAbsent, $absentDays);
            $batch = $this->batchRecorder->record($planner, self::TOOL_REPLACE, $batchItems);
            $this->actionGuard->complete($planner, (string) $intent->actionId, $batch?->id);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }

        $this->broadcast($resolved, $shifts->all(), $absentDays);

        $resultItems = array_map(
            static fn (AiWriteItemData $item): AiWriteItemData => new AiWriteItemData(
                $item->action,
                $item->id,
                $item->label,
                $item->start,
                $item->end,
                $item->room,
                [],
                $item->person,
            ),
            $items
        );

        return new AiWriteResultData(
            dry_run: false,
            tool: self::TOOL_REPLACE,
            batch_id: $batch?->id,
            undo_until: $batch?->undoUntil,
            ids: $shiftIds,
            items: $resultItems,
            links: $shifts->filter(static fn (Shift $shift): bool => $shift->event_id !== null)
                ->map(static fn (Shift $shift): AiWriteLinkData => new AiWriteLinkData(
                    id: (int) $shift->id,
                    url: route('dashboard.redirect-to-calendar', $shift->event_id),
                ))->values()->all(),
            summary: $this->summarize($resultItems),
        );
    }

    /**
     * Simulation: Zuweisungen von `from` entfernen, dann `to` je Person mit allen Zeilen bewerten
     * (und ggf. die Abwesenheiten von `from` beschreiben — ohne die zu ersetzenden Schichten).
     *
     * @param list<array<string, mixed>> $resolved
     * @param array<int, Shift> $shifts
     * @param array<string, list<string>> $absentDays
     * @return list<AiWriteItemData>
     */
    private function previewItems(
        User $planner,
        array $resolved,
        array $shifts,
        ?string $markAbsent,
        array $absentDays
    ): array {
        return $this->hypothesis->simulate(function () use (
            $planner,
            $resolved,
            $shifts,
            $markAbsent,
            $absentDays
        ): array {
            foreach ($resolved as $row) {
                if ($row['pivot'] instanceof ShiftWorker) {
                    $this->hypothesis->removeAssignment((int) $row['pivot']->id);
                }
            }

            $groups = [];
            foreach ($resolved as $row) {
                $key = AiPeopleService::typeOf($row['to_person']) . '#' . $row['to_person']->getKey();
                $groups[$key]['person'] = $row['to_person'];
                $groups[$key]['assignments'][] = [
                    'shift_id' => $row['shift_id'],
                    'shift_qualification_id' => $row['shift_qualification_id'],
                ];
            }

            $evaluated = [];
            foreach ($groups as $key => $group) {
                $preview = $this->single->previewItems($planner, $group['person'], $group['assignments'], [], $shifts);
                foreach ($preview as $item) {
                    $evaluated[$key . '|' . $item->id] = $item;
                }
            }

            $items = [];
            foreach ($resolved as $row) {
                $key = AiPeopleService::typeOf($row['to_person']) . '#' . $row['to_person']->getKey();
                $items[] = $this->rowItem(
                    $row,
                    $shifts[$row['shift_id']],
                    $evaluated[$key . '|' . $row['shift_id']] ?? null
                );
            }

            if ($markAbsent !== null) {
                foreach ($resolved as $row) {
                    $person = $row['from_person'];
                    $personKey = AiPeopleService::typeOf($person) . '#' . $person->getKey();
                    if (!isset($absentDays[$personKey])) {
                        continue;
                    }
                    array_push(
                        $items,
                        ...$this->absences->previewItems($person, $absentDays[$personKey], $markAbsent, null)
                    );
                    unset($absentDays[$personKey]);
                }
            }

            return $items;
        });
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowItem(array $row, Shift $shift, ?AiWriteItemData $evaluated): AiWriteItemData
    {
        $conflicts = $evaluated?->conflicts ?? [];
        $event = $shift->event;

        if (!$row['pivot'] instanceof ShiftWorker) {
            array_unshift($conflicts, new AiWriteConflictData(
                AiWriteConflictData::NOT_ASSIGNED,
                AiPeopleService::nameOf($row['from_person']) . ' ist in dieser Schicht nicht eingeteilt.',
                AiRefData::make($shift->event_id, $event === null ? null : ($event->eventName ?: $event->name))
            ));
        }

        $to = $row['to_person'];
        $interval = AiShiftPlanningService::intervalOf($shift);
        $timezone = (string) config('app.timezone');
        $roomId = $shift->room_id ?? $event?->room_id;

        return new AiWriteItemData(
            action: 'update',
            id: (int) $shift->id,
            label: AiText::truncate(
                AiPeopleService::nameOf($row['from_person']) . ' → ' . AiPeopleService::nameOf($to) . ': '
                . ($evaluated?->label ?? ($shift->craft?->name ?? ''))
            ),
            start: $evaluated?->start ?? ($interval[0] ?? null)?->copy()->setTimezone($timezone)->toIso8601String(),
            end: $evaluated?->end ?? ($interval[1] ?? null)?->copy()->setTimezone($timezone)->toIso8601String(),
            room: AiRefData::make($roomId === null ? null : (int) $roomId, $shift->room?->name ?? $event?->room?->name),
            conflicts: $conflicts,
            person: new AiPersonRefData(
                AiPeopleService::typeOf($to),
                (int) $to->getKey(),
                (string) AiText::truncate(AiPeopleService::nameOf($to)),
            ),
        );
    }

    /**
     * Tage (Schichtbeginn) je ausfallender Person.
     *
     * @param list<array<string, mixed>> $resolved
     * @param array<int, Shift> $shifts
     * @return array<string, list<string>>
     */
    private function absentDays(array $resolved, array $shifts): array
    {
        $days = [];

        foreach ($resolved as $row) {
            $interval = AiShiftPlanningService::intervalOf($shifts[$row['shift_id']]);
            if ($interval === null) {
                continue;
            }
            $key = AiPeopleService::typeOf($row['from_person']) . '#' . $row['from_person']->getKey();
            $days[$key][] = $interval[0]->toDateString();
        }

        return array_map(static function (array $list): array {
            $list = array_values(array_unique($list));
            sort($list);

            return $list;
        }, $days);
    }

    /**
     * @param list<AiWriteItemData> $items
     */
    private function assertExecutable(User $planner, AiWriteIntent $intent, array $items): void
    {
        $messages = [];
        $forbidden = false;

        foreach ($items as $item) {
            foreach ($item->conflicts as $conflict) {
                if (in_array($conflict->type, self::HINTS, true)) {
                    continue;
                }
                $forbidden = $forbidden || $conflict->type === AiWriteConflictData::MISSING_PERMISSION;
                $messages[] = $item->label . ': ' . $conflict->message;
            }
        }

        abort_if($forbidden, Response::HTTP_FORBIDDEN);

        $this->actionGuard->assertExecutable($planner, (string) $intent->actionId, self::TOOL_REPLACE, $intent->args);

        abort_if(
            $messages !== [],
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Ersetzen nicht moeglich: ' . implode(' | ', array_unique($messages))
        );
    }

    /**
     * In der Transaktion des Aufrufers. Batch-Reihenfolge fuer den Revert: Abwesenheiten, neue
     * Zuweisungen (`to`), entfernte Zuweisungen (`from`, mit Pivot-Zustand).
     *
     * @param list<array<string, mixed>> $resolved
     * @param array<int, Shift> $shifts
     * @param array<string, list<string>> $absentDays
     * @return list<array{model_type: string, model_id: int, action: string, before_state: array<string, mixed>|null}>
     */
    private function perform(
        User $planner,
        array $resolved,
        array $shifts,
        ?string $markAbsent,
        array $absentDays
    ): array {
        [$ruleFrom, $ruleTo] = $this->ruleWindow($shifts);
        $before = [];
        foreach ($resolved as $row) {
            if ($row['to_person'] instanceof User) {
                $before[$row['to_person']->id] ??= $this->hypothesis->violations($row['to_person'], $ruleFrom, $ruleTo);
            }
        }

        $created = [];
        $deleted = [];

        foreach ($resolved as $row) {
            $shift = $shifts[$row['shift_id']];
            /** @var ShiftWorker $pivot */
            $pivot = ShiftWorker::query()->findOrFail($row['pivot']->id);
            $deleted[] = [
                'model_type' => ShiftWorker::class,
                'model_id' => (int) $pivot->id,
                'action' => 'delete',
                'before_state' => $pivot->getRawOriginal(),
            ];

            $new = $this->replacementService->replace(
                $shift,
                $pivot,
                $row['to_person'],
                $row['shift_qualification_id'],
                (string) ($pivot->craft_abbreviation ?? $shift->craft?->abbreviation ?? ''),
                app(NotificationService::class),
                app(VacationConflictService::class),
                app(AvailabilityConflictService::class),
                app(ChangeService::class),
            );
            $created[] = [
                'model_type' => ShiftWorker::class,
                'model_id' => (int) $new->id,
                'action' => 'create',
                'before_state' => null,
            ];
        }

        foreach ($resolved as $row) {
            $to = $row['to_person'];
            if (!$to instanceof User || !isset($before[$to->id])) {
                continue;
            }

            $new = array_diff_key($this->hypothesis->violations($to, $ruleFrom, $ruleTo), $before[$to->id]);
            unset($before[$to->id]);
            abort_if(
                $new !== [],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Das Ersetzen verstiesse gegen Vertragsregeln von ' . AiPeopleService::nameOf($to) . '.'
            );
        }

        foreach ($resolved as $row) {
            if ($row['from_person'] instanceof User) {
                // Regeln neu pruefen wie nach „Ersetzen" in der Oberflaeche.
                $this->shiftRuleService->validateRulesForUser($row['from_person'], $ruleFrom->copy(), $ruleTo->copy());
            }
        }

        $vacations = [];
        if ($markAbsent !== null) {
            foreach ($resolved as $row) {
                $person = $row['from_person'];
                $key = AiPeopleService::typeOf($person) . '#' . $person->getKey();
                if (!isset($absentDays[$key])) {
                    continue;
                }
                array_push(
                    $vacations,
                    ...$this->absences->perform($planner, $person, $absentDays[$key], $markAbsent, null, null)
                );
                unset($absentDays[$key]);
            }
        }

        return array_merge($this->absences->batchItems($vacations), $created, $deleted);
    }

    /**
     * @param array<int, Shift> $shifts
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

        return [
            Carbon::parse(($from ?? Carbon::today())->toDateString())->subDay(),
            Carbon::parse(($to ?? Carbon::today())->toDateString())->addDay(),
        ];
    }

    /**
     * Nach dem Commit ueber `SafeBroadcast`: Austragen/Eintragen im Dienstplan anderer Clients.
     *
     * @param list<array<string, mixed>> $resolved
     * @param array<int, Shift> $shifts
     * @param array<string, list<string>> $absentDays
     */
    private function broadcast(array $resolved, array $shifts, array $absentDays): void
    {
        foreach ($resolved as $row) {
            $shift = $shifts[$row['shift_id']];
            $from = $row['from_person'];
            $roomId = $shift->event_id ? $shift->event?->room_id : $shift->room_id;

            if ($roomId !== null) {
                SafeBroadcast::send(new RemoveEntityFormShiftEvent(
                    $shift,
                    (int) $roomId,
                    (string) $from->getKey(),
                    match (true) {
                        $from instanceof User => '0',
                        $from instanceof Freelancer => '1',
                        default => '2',
                    }
                ));
            }

            $this->single->broadcast(
                $row['to_person'],
                [['shift' => $shift, 'qualification_id' => $row['shift_qualification_id']]],
                []
            );
        }

        $done = [];
        foreach ($resolved as $row) {
            $person = $row['from_person'];
            $key = AiPeopleService::typeOf($person) . '#' . $person->getKey();
            if (isset($absentDays[$key]) && !isset($done[$key])) {
                $this->absences->broadcast($person);
                $done[$key] = true;
            }
        }
    }

    /**
     * @param list<AiWriteItemData> $items
     */
    private function summarize(array $items): AiWriteSummaryData
    {
        $withConflicts = 0;
        $blocked = 0;

        foreach ($items as $item) {
            $blocking = array_filter(
                $item->conflicts,
                static fn (AiWriteConflictData $conflict): bool => !in_array($conflict->type, self::HINTS, true)
            );
            if ($item->conflicts !== []) {
                $withConflicts++;
            }
            if ($blocking !== []) {
                $blocked++;
            }
        }

        return new AiWriteSummaryData(self::TOOL_REPLACE, count($items), $withConflicts, $blocked);
    }
}
