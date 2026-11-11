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
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Events\RemoveEntityFormShiftEvent;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Services\ShiftAssignmentPreflightService;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\Shift\Support\SafeBroadcast;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * `POST /api/v1/ai/shift-assignments/remove` — Werkzeug `unassign_shifts` (ai-shift-planning,
 * Erweiterung 2 E).
 *
 * Ausgetragen wird ueber `ShiftWorkerService::removeFromShift()` wie in der Oberflaeche (inkl.
 * Benachrichtigung bei festgeschriebenen Schichten), danach Regellauf. Die Vorschau aendert nichts
 * und warnt mit `demand_opens`, wenn danach in der Funktion Bedarf offen ist (Hinweis, kein
 * Blocker). Blocker sind `not_assigned` (422) und `missing_permission` (403). Undo weist ueber den
 * gespeicherten Pivot-Zustand wieder zu (`AiActionBatchService::reassignFromState()`).
 */
class AiShiftUnassignWriteService
{
    public const TOOL_UNASSIGN = 'unassign_shifts';

    public function __construct(
        private readonly AiShiftPlanningService $planning,
        private readonly CraftScopeService $craftScope,
        private readonly ShiftWorkerService $shiftWorkerService,
        private readonly ShiftRuleService $shiftRuleService,
        private readonly ActionGuard $actionGuard,
        private readonly BatchRecorder $batchRecorder,
    ) {
    }

    /**
     * @param list<int> $shiftIds
     */
    public function unassign(
        User $planner,
        User|Freelancer|ServiceProvider $person,
        array $shiftIds,
        AiWriteIntent $intent
    ): AiWritePreviewData|AiWriteResultData {
        $shifts = Shift::query()
            ->visibleFor($planner)
            ->whereIn('id', $shiftIds)
            ->with(['craft', 'event', 'room'])
            ->orderBy('start_date')
            ->orderBy('start')
            ->get();
        abort_if($shifts->count() !== count($shiftIds), Response::HTTP_NOT_FOUND);

        $plannable = $this->craftScope->plannableCraftIdsFor($planner);
        $open = $this->planning->openDemand($shifts->modelKeys());
        $pivots = [];
        $items = [];

        foreach ($shifts as $shift) {
            $pivot = ShiftWorker::query()
                ->byEmployableIdAndShiftId($person->getMorphClass(), (int) $person->getKey(), (int) $shift->id)
                ->first();
            $pivots[$shift->id] = $pivot;
            $items[] = $this->item($shift, $pivot, $this->conflicts($shift, $pivot, $plannable, $open));
        }

        if ($intent->dryRun) {
            return new AiWritePreviewData(true, self::TOOL_UNASSIGN, $items, $this->summarize($items));
        }

        $this->assertExecutable($planner, $intent, $items);

        DB::beginTransaction();

        try {
            $batchItems = [];

            foreach ($shifts as $shift) {
                /** @var ShiftWorker $pivot */
                $pivot = $pivots[$shift->id];
                $batchItems[] = [
                    'model_type' => ShiftWorker::class,
                    'model_id' => (int) $pivot->id,
                    'action' => 'delete',
                    'before_state' => $pivot->getRawOriginal(),
                ];

                $this->shiftWorkerService->removeFromShift(
                    $pivot,
                    true,
                    app(NotificationService::class),
                    app(VacationConflictService::class),
                    app(AvailabilityConflictService::class),
                    app(ChangeService::class),
                );
            }

            if ($person instanceof User) {
                // Regeln neu pruefen wie nach „Aus Schicht entfernen" in der Oberflaeche.
                $this->shiftRuleService->validateRulesForUser(
                    $person,
                    Carbon::parse($shifts->min('start_date'))->subDay(),
                    Carbon::parse($shifts->max('end_date') ?? $shifts->max('start_date'))->addDay()
                );
            }

            $batch = $this->batchRecorder->record($planner, self::TOOL_UNASSIGN, $batchItems);
            $this->actionGuard->complete($planner, (string) $intent->actionId, $batch?->id);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }

        $this->broadcast($person, $shifts->all());

        return new AiWriteResultData(
            dry_run: false,
            tool: self::TOOL_UNASSIGN,
            batch_id: $batch?->id,
            undo_until: $batch?->undoUntil,
            ids: $shifts->map(static fn (Shift $shift): int => (int) $shift->id)->all(),
            items: $items,
            links: $shifts->filter(static fn (Shift $shift): bool => $shift->event_id !== null)
                ->map(static fn (Shift $shift): AiWriteLinkData => new AiWriteLinkData(
                    id: (int) $shift->id,
                    url: route('dashboard.redirect-to-calendar', $shift->event_id),
                ))->values()->all(),
            summary: $this->summarize($items),
        );
    }

    /**
     * @param list<int>|null $plannable
     * @param array<int, array<int, int>> $open
     * @return list<AiWriteConflictData>
     */
    private function conflicts(Shift $shift, ?ShiftWorker $pivot, ?array $plannable, array $open): array
    {
        $event = AiRefData::make(
            $shift->event_id,
            $shift->event === null ? null : ($shift->event->eventName ?: $shift->event->name)
        );
        $conflicts = [];

        if ($plannable !== null && !in_array((int) $shift->craft_id, $plannable, true)) {
            $conflicts[] = new AiWriteConflictData(
                AiWriteConflictData::MISSING_PERMISSION,
                'Dieses Gewerk darf die planende Person nicht planen.',
                $event
            );
        }

        if ($pivot === null) {
            $conflicts[] = new AiWriteConflictData(
                AiWriteConflictData::NOT_ASSIGNED,
                'Die Person ist in dieser Schicht nicht eingeteilt.',
                $event
            );

            return $conflicts;
        }

        $qualificationId = (int) $pivot->shift_qualification_id;
        $openAfter = ($open[$shift->id][$qualificationId] ?? 0) + ($pivot->is_overbooked ? 0 : 1);

        if ($openAfter > 0) {
            $conflicts[] = new AiWriteConflictData(
                AiWriteConflictData::DEMAND_OPENS,
                "Danach sind in dieser Funktion {$openAfter} Platz/Plaetze offen.",
                $event
            );
        }

        return $conflicts;
    }

    /**
     * @param list<AiWriteItemData> $items
     */
    private function assertExecutable(User $planner, AiWriteIntent $intent, array $items): void
    {
        $blocking = [];
        $forbidden = false;

        foreach ($items as $item) {
            foreach ($item->conflicts as $conflict) {
                if ($conflict->type === AiWriteConflictData::DEMAND_OPENS) {
                    continue;
                }
                $forbidden = $forbidden || $conflict->type === AiWriteConflictData::MISSING_PERMISSION;
                $blocking[] = $item->label . ': ' . $conflict->message;
            }
        }

        abort_if($forbidden, Response::HTTP_FORBIDDEN);

        $this->actionGuard->assertExecutable($planner, (string) $intent->actionId, self::TOOL_UNASSIGN, $intent->args);

        abort_if(
            $blocking !== [],
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Austragen nicht moeglich: ' . implode(' | ', $blocking)
        );
    }

    /**
     * @param list<Shift> $shifts
     */
    private function broadcast(User|Freelancer|ServiceProvider $person, array $shifts): void
    {
        $entityType = match (true) {
            $person instanceof User => '0',
            $person instanceof Freelancer => '1',
            default => '2',
        };

        foreach ($shifts as $shift) {
            $roomId = $shift->event_id ? $shift->event?->room_id : $shift->room_id;

            if ($roomId !== null) {
                SafeBroadcast::send(
                    new RemoveEntityFormShiftEvent($shift, (int) $roomId, (string) $person->getKey(), $entityType)
                );
            }
        }
    }

    /**
     * @param list<AiWriteConflictData> $conflicts
     */
    private function item(Shift $shift, ?ShiftWorker $pivot, array $conflicts): AiWriteItemData
    {
        $event = $shift->event;
        $interval = $pivot === null
            ? AiShiftPlanningService::intervalOf($shift)
            : ShiftAssignmentPreflightService::resolvePivotInterval($pivot, $shift);
        $qualification = $pivot === null
            ? null
            : ShiftQualification::query()->find($pivot->shift_qualification_id)?->name;
        $label = implode(' · ', array_filter([
            $shift->craft?->name,
            $event === null ? null : ($event->eventName ?: $event->name),
        ])) . ($qualification ? " ({$qualification})" : '');
        $roomId = $shift->room_id ?? $event?->room_id;
        $timezone = (string) config('app.timezone');

        return new AiWriteItemData(
            action: 'delete',
            id: (int) $shift->id,
            label: AiText::truncate($label),
            start: isset($interval[0]) ? $interval[0]->copy()->setTimezone($timezone)->toIso8601String() : null,
            end: isset($interval[1]) ? $interval[1]->copy()->setTimezone($timezone)->toIso8601String() : null,
            room: AiRefData::make($roomId === null ? null : (int) $roomId, $shift->room?->name ?? $event?->room?->name),
            conflicts: $conflicts,
        );
    }

    /**
     * @param list<AiWriteItemData> $items
     */
    private function summarize(array $items): AiWriteSummaryData
    {
        $withConflicts = 0;
        $blocked = 0;

        foreach ($items as $item) {
            if ($item->conflicts !== []) {
                $withConflicts++;
            }

            foreach ($item->conflicts as $conflict) {
                $blocking = [AiWriteConflictData::MISSING_PERMISSION, AiWriteConflictData::NOT_ASSIGNED];
                if (in_array($conflict->type, $blocking, true)) {
                    $blocked++;
                    break;
                }
            }
        }

        return new AiWriteSummaryData(self::TOOL_UNASSIGN, count($items), $withConflicts, $blocked);
    }
}
