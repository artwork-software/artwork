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
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftScopeService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Services\LegalBreakCalculator;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Support\CreatedShiftsPublisher;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Schichten anlegen und kopieren (ai-scenarios-4 S13) — Werkzeuge `create_shifts` und `copy_shifts`.
 *
 * Angelegt wird wie beim Anlegen in der Oberflaeche (`ShiftController::storeShiftWithoutEvent()`):
 * `ShiftService::save()`, ohne Pause die gesetzliche Mindestpause, Plaetze ueber
 * `ShiftsQualificationsService`, danach `CreatedShiftsPublisher` (Broadcast nach dem Commit). Hier mit
 * Termin: Raum und Projekt kommen vom Termin. Rechte: Schichtplaner (Controller) und Gewerks-Scope
 * (`missing_permission` → 403). Die Vorschau beschreibt nur (nichts wird angelegt).
 *
 * Kopieren: die Schichten des Quelltermins (optional ein Gewerk) mit Uhrzeiten relativ zum
 * Terminbeginn, Pause, Beschreibung und allen Plaetzen. Mit `with_assignments` werden die
 * Zuweisungen mitgenommen — aber nur, wer dabei regelkonform bleibt: Bewertung wie `assign_shifts`
 * (`AiShiftAssignmentWriteService::previewItems()`) gegen die hypothetisch angelegten Schichten,
 * je Person alle ihre Zuweisungen zusammen. Wer einen Konflikt haette, wird uebersprungen
 * (`assignment_skipped` plus Gruende) und nicht eingetragen; die Ausfuehrung bewertet erneut
 * gegen die echten neuen Schichten.
 *
 * Undo loescht die Schichten (vorher die mitkopierten Zuweisungen) — nur solange niemand sonst
 * eingetragen ist, sonst 409 (`AiActionBatchService`).
 *
 * @phpstan-type ShiftSpec array{
 *     craft_id: int, start: Carbon, end: Carbon, break_minutes: int, description: string|null,
 *     qualifications: list<array{qualification_id: int, value: int}>, source_shift_id: int|null
 * }
 */
class AiShiftCreateWriteService
{
    public const TOOL_CREATE = 'create_shifts';

    public const TOOL_COPY = 'copy_shifts';

    public function __construct(
        private readonly CraftScopeService $craftScope,
        private readonly ShiftService $shiftService,
        private readonly ShiftsQualificationsService $shiftsQualificationsService,
        private readonly AiShiftAssignmentWriteService $single,
        private readonly AiShiftHypothesis $hypothesis,
        private readonly ActionGuard $actionGuard,
        private readonly BatchRecorder $batchRecorder,
    ) {
    }

    /**
     * @param list<array{
     *     craft_id: int, start: string, end: string, start_day: string|null, end_day: string|null,
     *     break_minutes: int|null, description: string|null,
     *     qualifications: list<array{qualification_id: int, value: int}>
     * }> $rows
     */
    public function create(
        User $planner,
        int $eventId,
        array $rows,
        AiWriteIntent $intent
    ): AiWritePreviewData|AiWriteResultData {
        $event = $this->visibleEvent($planner, $eventId);
        $specs = array_map(fn (array $row): array => $this->specFromRow($event, $row), $rows);
        $items = $this->shiftItems($planner, $event, $specs);

        if ($intent->dryRun) {
            return new AiWritePreviewData(true, self::TOOL_CREATE, $items, $this->summarize(self::TOOL_CREATE, $items));
        }

        $this->assertExecutable($planner, $intent, self::TOOL_CREATE, $items);

        DB::beginTransaction();

        try {
            $created = array_map(fn (array $spec): Shift => $this->createShift($event, $spec), $specs);
            $batch = $this->batchRecorder->record($planner, self::TOOL_CREATE, $this->shiftBatchItems($created));
            $this->actionGuard->complete($planner, (string) $intent->actionId, $batch?->id);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }

        $this->publish($created);

        return $this->result(self::TOOL_CREATE, $event, $created, $this->stripConflicts($items, $created), $batch);
    }

    public function copy(
        User $planner,
        int $sourceEventId,
        int $targetEventId,
        ?int $craftId,
        bool $withAssignments,
        AiWriteIntent $intent
    ): AiWritePreviewData|AiWriteResultData {
        $source = $this->visibleEvent($planner, $sourceEventId);
        $target = $this->visibleEvent($planner, $targetEventId);
        abort_if($craftId !== null && Craft::query()->whereKey($craftId)->doesntExist(), Response::HTTP_NOT_FOUND);

        $sourceShifts = Shift::query()
            ->visibleFor($planner)
            ->where('event_id', $source->id)
            ->when($craftId !== null, static fn ($query) => $query->where('craft_id', $craftId))
            ->with(['shiftsQualifications', 'craft'])
            ->orderBy('start_date')
            ->orderBy('start')
            ->orderBy('id')
            ->get();
        abort_if(
            $sourceShifts->isEmpty(),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Der Quelltermin hat keine Schichten' . ($craftId === null ? '.' : ' in diesem Gewerk.')
        );

        $offset = (int) round(
            Carbon::parse($source->start_time)->diffInMinutes(Carbon::parse($target->start_time), false)
        );
        $specs = [];
        foreach ($sourceShifts as $shift) {
            $spec = $this->specFromShift($shift, $offset);
            if ($spec !== null) {
                $specs[] = $spec;
            }
        }

        $assignments = $withAssignments ? $this->sourceAssignments($specs) : [];
        $items = array_merge(
            $this->shiftItems($planner, $target, $specs),
            $assignments === [] ? [] : $this->hypothesis->simulate(
                fn (): array => $this->assignmentItems(
                    $planner,
                    $assignments,
                    $this->hypotheticalShifts($target, $specs)
                )
            )
        );

        if ($intent->dryRun) {
            return new AiWritePreviewData(true, self::TOOL_COPY, $items, $this->summarize(self::TOOL_COPY, $items));
        }

        $this->assertExecutable($planner, $intent, self::TOOL_COPY, $items);

        DB::beginTransaction();

        try {
            $created = array_map(fn (array $spec): Shift => $this->createShift($target, $spec), $specs);
            [$assignmentItems, $performed, $workerBatchItems] = $this->copyAssignments(
                $planner,
                $assignments,
                $created
            );

            $batch = $this->batchRecorder->record(
                $planner,
                self::TOOL_COPY,
                array_merge($workerBatchItems, $this->shiftBatchItems($created))
            );
            $this->actionGuard->complete($planner, (string) $intent->actionId, $batch?->id);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }

        $this->publish($created);
        foreach ($performed as [$person, $targets]) {
            $this->single->broadcast($person, $targets, []);
        }

        $shiftItems = $this->stripConflicts(array_slice($items, 0, count($specs)), $created);

        return $this->result(self::TOOL_COPY, $target, $created, array_merge($shiftItems, $assignmentItems), $batch);
    }

    private function visibleEvent(User $planner, int $eventId): Event
    {
        $event = Event::query()->visibleFor($planner)->with('room')->find($eventId);
        abort_if($event === null, Response::HTTP_NOT_FOUND);

        return $event;
    }

    /**
     * @param array{
     *     craft_id: int, start: string, end: string, start_day: string|null, end_day: string|null,
     *     break_minutes: int|null, description: string|null,
     *     qualifications: list<array{qualification_id: int, value: int}>
     * } $row
     * @return ShiftSpec
     */
    private function specFromRow(Event $event, array $row): array
    {
        $startDay = $row['start_day'] ?? Carbon::parse($event->start_time)->toDateString();
        $start = Carbon::parse($startDay . ' ' . $row['start']);
        $end = Carbon::parse(($row['end_day'] ?? $startDay) . ' ' . $row['end']);

        if ($row['end_day'] === null && $end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        abort_if(
            $end->lessThanOrEqualTo($start),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Das Schichtende muss nach dem Beginn liegen.'
        );

        return [
            'craft_id' => $row['craft_id'],
            'start' => $start,
            'end' => $end,
            'break_minutes' => LegalBreakCalculator::resolveBreakMinutes(
                $row['break_minutes'],
                $start->format('H:i'),
                $end->format('H:i')
            ),
            'description' => $row['description'],
            'qualifications' => $row['qualifications'],
            'source_shift_id' => null,
        ];
    }

    /**
     * @return ShiftSpec|null
     */
    private function specFromShift(Shift $shift, int $offsetMinutes): ?array
    {
        $interval = AiShiftPlanningService::intervalOf($shift);
        if ($interval === null) {
            return null;
        }

        $qualifications = [];
        foreach ($shift->shiftsQualifications as $row) {
            if ((int) $row->value > 0) {
                $qualifications[] = [
                    'qualification_id' => (int) $row->shift_qualification_id,
                    'value' => (int) $row->value,
                ];
            }
        }

        return [
            'craft_id' => (int) $shift->craft_id,
            'start' => $interval[0]->copy()->addMinutes($offsetMinutes),
            'end' => $interval[1]->copy()->addMinutes($offsetMinutes),
            'break_minutes' => (int) $shift->break_minutes,
            'description' => $shift->description,
            'qualifications' => $qualifications,
            'source_shift_id' => (int) $shift->id,
        ];
    }

    /**
     * Zuweisungen der Quellschichten: Index der neuen Schicht, Person, Funktion.
     *
     * @param list<ShiftSpec> $specs
     * @return list<array{index: int, person: User|Freelancer|ServiceProvider, qualification_id: int}>
     */
    private function sourceAssignments(array $specs): array
    {
        $assignments = [];

        foreach ($specs as $index => $spec) {
            $pivots = ShiftWorker::query()->where('shift_id', $spec['source_shift_id'])->orderBy('id')->get();
            foreach ($pivots as $pivot) {
                $person = $pivot->employable;
                if ($person instanceof User || $person instanceof Freelancer || $person instanceof ServiceProvider) {
                    $assignments[] = [
                        'index' => $index,
                        'person' => $person,
                        'qualification_id' => (int) $pivot->shift_qualification_id,
                    ];
                }
            }
        }

        return $assignments;
    }

    /**
     * Nur innerhalb von `simulate()`: die neuen Schichten als Zeilen ohne Services.
     *
     * @param list<ShiftSpec> $specs
     * @return list<Shift>
     */
    private function hypotheticalShifts(Event $target, array $specs): array
    {
        $now = now();
        $shifts = [];

        foreach ($specs as $spec) {
            $shiftId = (int) DB::table('shifts')->insertGetId([
                'event_id' => $target->id,
                'craft_id' => $spec['craft_id'],
                'start_date' => $spec['start']->toDateString(),
                'end_date' => $spec['end']->toDateString(),
                'start' => $spec['start']->format('H:i:s'),
                'end' => $spec['end']->format('H:i:s'),
                'break_minutes' => $spec['break_minutes'],
                'is_committed' => false,
                'in_workflow' => false,
                'shift_uuid' => (string) Str::uuid(),
                'event_start_day' => $spec['start']->toDateString(),
                'event_end_day' => $spec['end']->toDateString(),
                'room_id' => $target->room_id,
                'project_id' => $target->project_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($spec['qualifications'] as $qualification) {
                DB::table('shifts_qualifications')->insert([
                    'shift_id' => $shiftId,
                    'shift_qualification_id' => $qualification['qualification_id'],
                    'value' => $qualification['value'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $shifts[] = Shift::query()->with(['craft', 'event', 'room'])->findOrFail($shiftId);
        }

        return $shifts;
    }

    /**
     * Bewertung der mitzukopierenden Zuweisungen gegen die neuen Schichten (je Person zusammen).
     *
     * @param list<array{index: int, person: User|Freelancer|ServiceProvider, qualification_id: int}> $assignments
     * @param list<Shift> $shifts
     * @return list<AiWriteItemData>
     */
    private function assignmentItems(User $planner, array $assignments, array $shifts): array
    {
        $byId = [];
        foreach ($shifts as $shift) {
            $byId[(int) $shift->id] = $shift;
        }

        $groups = [];
        foreach ($assignments as $assignment) {
            $person = $assignment['person'];
            $key = AiPeopleService::typeOf($person) . '#' . $person->getKey();
            $groups[$key]['person'] = $person;
            $groups[$key]['assignments'][] = [
                'shift_id' => (int) $shifts[$assignment['index']]->id,
                'shift_qualification_id' => $assignment['qualification_id'],
            ];
        }

        $items = [];
        foreach ($groups as $group) {
            $person = $group['person'];
            $ref = new AiPersonRefData(
                AiPeopleService::typeOf($person),
                (int) $person->getKey(),
                (string) AiText::truncate(AiPeopleService::nameOf($person)),
            );

            foreach ($this->single->previewItems($planner, $person, $group['assignments'], [], $byId) as $item) {
                $skipped = $item->conflicts !== [];
                $items[] = new AiWriteItemData(
                    action: 'create',
                    id: null,
                    label: AiText::truncate(($skipped ? 'Uebersprungen: ' : '') . $ref->name . ': ' . $item->label),
                    start: $item->start,
                    end: $item->end,
                    room: $item->room,
                    conflicts: $skipped ? array_merge([new AiWriteConflictData(
                        AiWriteConflictData::ASSIGNMENT_SKIPPED,
                        'Diese Zuweisung wird nicht mitkopiert.',
                        null
                    )], $item->conflicts) : [],
                    person: $ref,
                );
            }
        }

        return $items;
    }

    /**
     * In der Ausfuehrungs-Transaktion: erneut bewerten (gegen die echten neuen Schichten), nur
     * konfliktfreie Personen eintragen.
     *
     * @param list<array{index: int, person: User|Freelancer|ServiceProvider, qualification_id: int}> $assignments
     * @param list<Shift> $created
     * @return array{
     *     0: list<AiWriteItemData>,
     *     1: list<array{0: User|Freelancer|ServiceProvider, 1: list<array{shift: Shift, qualification_id: int}>}>,
     *     2: list<array{model_type: string, model_id: int, action: string, before_state: null}>
     * }
     */
    private function copyAssignments(User $planner, array $assignments, array $created): array
    {
        if ($assignments === []) {
            return [[], [], []];
        }

        $byId = [];
        foreach ($created as $shift) {
            $byId[(int) $shift->id] = $shift;
        }

        $groups = [];
        foreach ($assignments as $assignment) {
            $person = $assignment['person'];
            $key = AiPeopleService::typeOf($person) . '#' . $person->getKey();
            $groups[$key]['person'] = $person;
            $groups[$key]['assignments'][] = [
                'shift_id' => (int) $created[$assignment['index']]->id,
                'shift_qualification_id' => $assignment['qualification_id'],
            ];
        }

        $items = [];
        $performed = [];
        $batchItems = [];

        foreach ($groups as $group) {
            $person = $group['person'];
            $accepted = [];
            foreach ($this->single->previewItems($planner, $person, $group['assignments'], [], $byId) as $item) {
                if ($item->conflicts === []) {
                    $accepted[] = (int) $item->id;
                }
            }

            $rows = array_values(array_filter(
                $group['assignments'],
                static fn (array $row): bool => in_array($row['shift_id'], $accepted, true)
            ));
            if ($rows === []) {
                continue;
            }

            $result = $this->single->perform($person, $rows, [], $byId);
            $performed[] = [$person, $result['targets']];
            $batchItems = array_merge($batchItems, $result['batch_items']);

            $ref = new AiPersonRefData(
                AiPeopleService::typeOf($person),
                (int) $person->getKey(),
                (string) AiText::truncate(AiPeopleService::nameOf($person)),
            );
            foreach ($result['targets'] as $target) {
                $item = $this->single->resultItem($target);
                $items[] = new AiWriteItemData(
                    'create',
                    $item->id,
                    AiText::truncate($ref->name . ': ' . $item->label),
                    $item->start,
                    $item->end,
                    $item->room,
                    [],
                    $ref,
                );
            }
        }

        return [$items, $performed, $batchItems];
    }

    /**
     * @param list<ShiftSpec> $specs
     * @return list<AiWriteItemData>
     */
    private function shiftItems(User $planner, Event $event, array $specs): array
    {
        $plannable = $this->craftScope->plannableCraftIdsFor($planner);
        $crafts = Craft::query()->whereIn('id', array_column($specs, 'craft_id'))->pluck('name', 'id')->all();
        $names = ShiftQualification::query()->pluck('name', 'id')->all();
        $timezone = (string) config('app.timezone');
        $eventRef = AiRefData::make($event->id, AiText::truncate($event->eventName ?: $event->name));
        $items = [];

        foreach ($specs as $spec) {
            $places = implode(', ', array_map(
                static fn (array $row): string => $row['value'] . '× ' . ($names[$row['qualification_id']] ?? '?'),
                $spec['qualifications']
            ));
            $conflicts = [];
            if ($plannable !== null && !in_array($spec['craft_id'], $plannable, true)) {
                $conflicts[] = new AiWriteConflictData(
                    AiWriteConflictData::MISSING_PERMISSION,
                    'Dieses Gewerk darf die planende Person nicht planen.',
                    $eventRef
                );
            }

            $items[] = new AiWriteItemData(
                action: 'create',
                id: null,
                label: AiText::truncate(implode(' · ', array_filter([
                    $crafts[$spec['craft_id']] ?? null,
                    $event->eventName ?: $event->name,
                ])) . ($places === '' ? '' : " ({$places})")),
                start: $spec['start']->copy()->setTimezone($timezone)->toIso8601String(),
                end: $spec['end']->copy()->setTimezone($timezone)->toIso8601String(),
                room: AiRefData::make($event->room_id, $event->room?->name),
                conflicts: $conflicts,
            );
        }

        return $items;
    }

    /**
     * Wie `ShiftController::storeShiftWithoutEvent()`, mit Termin.
     *
     * @param ShiftSpec $spec
     */
    private function createShift(Event $event, array $spec): Shift
    {
        $shift = new Shift([
            'event_id' => $event->id,
            'start_date' => $spec['start']->toDateString(),
            'end_date' => $spec['end']->toDateString(),
            'start' => $spec['start']->format('H:i'),
            'end' => $spec['end']->format('H:i'),
            'break_minutes' => $spec['break_minutes'],
            'description' => $spec['description'],
            'room_id' => $event->room_id,
            'project_id' => $event->project_id,
            'event_start_day' => $spec['start']->toDateString(),
            'event_end_day' => $spec['end']->toDateString(),
            'shift_uuid' => (string) Str::uuid(),
        ]);
        $shift->craft()->associate($spec['craft_id']);
        $this->shiftService->save($shift);

        foreach ($spec['qualifications'] as $qualification) {
            $this->shiftsQualificationsService->createShiftsQualificationForShift((int) $shift->id, [
                'shift_qualification_id' => $qualification['qualification_id'],
                'value' => $qualification['value'],
            ]);
        }

        return $shift->fresh(['craft', 'event', 'room']) ?? $shift;
    }

    /**
     * @param list<Shift> $created
     * @return list<array{model_type: string, model_id: int, action: string, before_state: null}>
     */
    private function shiftBatchItems(array $created): array
    {
        return array_map(static fn (Shift $shift): array => [
            'model_type' => Shift::class,
            'model_id' => (int) $shift->id,
            'action' => 'create',
            'before_state' => null,
        ], $created);
    }

    /**
     * @param list<Shift> $created
     */
    private function publish(array $created): void
    {
        CreatedShiftsPublisher::publish(collect(array_map(
            static fn (Shift $shift): ?Shift => $shift->fresh(),
            $created
        ))->filter(), false);
    }

    /**
     * Ergebnis-Items der Schichten: IDs der angelegten Schichten, ohne Konflikte.
     *
     * @param list<AiWriteItemData> $items
     * @param list<Shift> $created
     * @return list<AiWriteItemData>
     */
    private function stripConflicts(array $items, array $created): array
    {
        return array_values(array_map(
            static fn (AiWriteItemData $item, Shift $shift): AiWriteItemData => new AiWriteItemData(
                $item->action,
                (int) $shift->id,
                $item->label,
                $item->start,
                $item->end,
                $item->room,
                [],
            ),
            $items,
            $created
        ));
    }

    /**
     * @param list<AiWriteItemData> $items
     */
    private function assertExecutable(User $planner, AiWriteIntent $intent, string $tool, array $items): void
    {
        foreach ($items as $item) {
            foreach ($item->conflicts as $conflict) {
                abort_if($conflict->type === AiWriteConflictData::MISSING_PERMISSION, Response::HTTP_FORBIDDEN);
            }
        }

        $this->actionGuard->assertExecutable($planner, (string) $intent->actionId, $tool, $intent->args);
    }

    /**
     * @param list<Shift> $created
     * @param list<AiWriteItemData> $items
     */
    private function result(
        string $tool,
        Event $event,
        array $created,
        array $items,
        ?\Artwork\Modules\AiAssistant\Support\RecordedBatch $batch
    ): AiWriteResultData {
        return new AiWriteResultData(
            dry_run: false,
            tool: $tool,
            batch_id: $batch?->id,
            undo_until: $batch?->undoUntil,
            ids: array_map(static fn (Shift $shift): int => (int) $shift->id, $created),
            items: $items,
            links: [new AiWriteLinkData(
                id: (int) $event->id,
                url: route('dashboard.redirect-to-calendar', $event->id),
            )],
            summary: $this->summarize($tool, $items),
        );
    }

    /**
     * `blocked` zaehlt fehlende Rechte; uebersprungene Zuweisungen zaehlen nur als Konflikt.
     *
     * @param list<AiWriteItemData> $items
     */
    private function summarize(string $tool, array $items): AiWriteSummaryData
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

        return new AiWriteSummaryData($tool, count($items), $withConflicts, $blocked);
    }
}
