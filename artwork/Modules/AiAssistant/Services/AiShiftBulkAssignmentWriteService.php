<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\Contracts\ActionGuard;
use Artwork\Modules\AiAssistant\Contracts\BatchRecorder;
use Artwork\Modules\AiAssistant\DTOs\AiPersonRefData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteConflictData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteItemData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteLinkData;
use Artwork\Modules\AiAssistant\DTOs\AiWritePreviewData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteResultData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\AiAssistant\Support\AiWriteIntent;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * `POST /api/v1/ai/shift-assignments/bulk` — Werkzeug `assign_shifts_bulk` (ai-scenarios-3 S3).
 *
 * Mehrere Personen in einem Aufruf. Vorschau je Person mit derselben hypothetischen Bewertung wie
 * `assign_shifts` (`AiShiftAssignmentWriteService::previewItems()`, Regeln hart); die Ausfuehrung
 * prueft erneut, schreibt alle Personen in **einer** Transaktion ueber
 * `AiShiftAssignmentWriteService::perform()` und zeichnet **einen** Batch auf — Undo nimmt alle
 * Zuweisungen zurueck.
 */
class AiShiftBulkAssignmentWriteService
{
    public const TOOL_BULK = 'assign_shifts_bulk';

    public function __construct(
        private readonly AiPeopleService $people,
        private readonly AiShiftAssignmentWriteService $single,
        private readonly AiShiftPlanningService $planning,
        private readonly ActionGuard $actionGuard,
        private readonly BatchRecorder $batchRecorder,
    ) {
    }

    /**
     * @param list<array{person: array{type: string, id: int}, shift_id: int, shift_qualification_id: int}> $rows
     */
    public function assign(User $planner, array $rows, AiWriteIntent $intent): AiWritePreviewData|AiWriteResultData
    {
        $shifts = Shift::query()
            ->visibleFor($planner)
            ->whereIn('id', array_unique(array_column($rows, 'shift_id')))
            ->with(['craft', 'event', 'room'])
            ->get()
            ->keyBy('id');
        abort_if(
            $shifts->count() !== count(array_unique(array_column($rows, 'shift_id'))),
            Response::HTTP_NOT_FOUND
        );

        $groups = [];
        foreach ($rows as $row) {
            $key = $row['person']['type'] . '#' . $row['person']['id'];
            $groups[$key] ??= [
                'person' => $this->people->find($row['person']['type'], $row['person']['id']),
                'assignments' => [],
            ];
            $groups[$key]['assignments'][] = [
                'shift_id' => $row['shift_id'],
                'shift_qualification_id' => $row['shift_qualification_id'],
            ];
        }

        $overbooked = $this->overbookedRows($rows, $shifts->modelKeys());
        $items = [];
        foreach ($groups as $key => $group) {
            $ref = $this->personRef($group['person']);
            $preview = $this->single->previewItems(
                $planner,
                $group['person'],
                $group['assignments'],
                [],
                $shifts->all()
            );
            foreach ($preview as $item) {
                if (isset($overbooked[$key . '|' . $item->id])) {
                    $item->conflicts[] = new AiWriteConflictData(
                        AiWriteConflictData::NO_OPEN_DEMAND,
                        'Mehr Personen als offene Plaetze in dieser Funktion.',
                        null
                    );
                }
                $items[] = $this->withPerson($item, $ref);
            }
        }

        if ($intent->dryRun) {
            return new AiWritePreviewData(
                true,
                self::TOOL_BULK,
                $items,
                $this->single->summarize($items, self::TOOL_BULK)
            );
        }

        $this->single->assertExecutable($planner, $intent, $items, self::TOOL_BULK);

        DB::beginTransaction();

        try {
            $performed = [];
            $batchItems = [];

            foreach ($groups as $group) {
                $result = $this->single->perform($group['person'], $group['assignments'], [], $shifts->all());
                $performed[] = [$group['person'], $result['targets']];
                $batchItems = array_merge($batchItems, $result['batch_items']);
            }

            $batch = $this->batchRecorder->record($planner, self::TOOL_BULK, $batchItems);
            $this->actionGuard->complete($planner, (string) $intent->actionId, $batch?->id);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }

        $resultItems = [];
        $ids = [];
        $links = [];

        foreach ($performed as [$person, $targets]) {
            $this->single->broadcast($person, $targets, []);
            $ref = $this->personRef($person);

            foreach ($targets as $target) {
                $resultItems[] = $this->withPerson($this->single->resultItem($target), $ref);
                $ids[] = (int) $target['shift']->id;

                if ($target['shift']->event_id !== null) {
                    $links[(int) $target['shift']->id] = new AiWriteLinkData(
                        id: (int) $target['shift']->id,
                        url: route('dashboard.redirect-to-calendar', $target['shift']->event_id),
                    );
                }
            }
        }

        return new AiWriteResultData(
            dry_run: false,
            tool: self::TOOL_BULK,
            batch_id: $batch?->id,
            undo_until: $batch?->undoUntil,
            ids: array_values(array_unique($ids)),
            items: $resultItems,
            links: array_values($links),
            summary: $this->single->summarize($resultItems, self::TOOL_BULK),
        );
    }

    /**
     * Zeilen, die ueber den offenen Bedarf je Schicht und Funktion hinausgehen (die Vorschau je
     * Person sieht die anderen Personen nicht). Schluessel `typ#id|schicht`.
     *
     * @param list<array{person: array{type: string, id: int}, shift_id: int, shift_qualification_id: int}> $rows
     * @param list<int> $shiftIds
     * @return array<string, true>
     */
    private function overbookedRows(array $rows, array $shiftIds): array
    {
        $open = $this->planning->openDemand($shiftIds);
        $used = [];
        $overbooked = [];

        foreach ($rows as $row) {
            $slot = $row['shift_id'] . '|' . $row['shift_qualification_id'];
            $used[$slot] = ($used[$slot] ?? 0) + 1;

            if ($used[$slot] > ($open[$row['shift_id']][$row['shift_qualification_id']] ?? 0)) {
                $overbooked[$row['person']['type'] . '#' . $row['person']['id'] . '|' . $row['shift_id']] = true;
            }
        }

        return $overbooked;
    }

    private function personRef(\Illuminate\Database\Eloquent\Model $person): AiPersonRefData
    {
        return new AiPersonRefData(
            AiPeopleService::typeOf($person),
            (int) $person->getKey(),
            (string) AiText::truncate(AiPeopleService::nameOf($person)),
        );
    }

    private function withPerson(AiWriteItemData $item, AiPersonRefData $person): AiWriteItemData
    {
        return new AiWriteItemData(
            action: $item->action,
            id: $item->id,
            label: AiText::truncate($person->name . ': ' . $item->label),
            start: $item->start,
            end: $item->end,
            room: $item->room,
            conflicts: $item->conflicts,
            person: $person,
        );
    }
}
