<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiCraftMissingData;
use Artwork\Modules\AiAssistant\DTOs\AiDayAbsenceData;
use Artwork\Modules\AiAssistant\DTOs\AiDayAbsencesData;
use Artwork\Modules\AiAssistant\DTOs\AiDayEventData;
use Artwork\Modules\AiAssistant\DTOs\AiDayEventListData;
use Artwork\Modules\AiAssistant\DTOs\AiDayOpenShiftsData;
use Artwork\Modules\AiAssistant\DTOs\AiDayOverviewData;
use Artwork\Modules\AiAssistant\DTOs\AiDayPlannerData;
use Artwork\Modules\AiAssistant\DTOs\AiDayRoomConflictsData;
use Artwork\Modules\AiAssistant\DTOs\AiPersonRefData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\Support\AiShiftPlanningAccess;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\AppApi\Services\AppDashboardService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Tagesuebersicht (ai-scenarios-3 S1). Baut auf denselben Abfragen wie das Dashboard der App auf
 * (`AppDashboardService::eventsOfDayQuery()`, `ownTasksQuery()`), eigene Schichten wie
 * `/ai/me/schedule`, der Planer-Block auf den Planungs-Lesediensten (offene Schichten im
 * Gewerks-Scope, Abwesenheiten, Doppelbelegungen).
 */
class AiDayOverviewService
{
    private const TASK_LIMIT = 10;

    private const PLANNER_LIST_LIMIT = 10;

    public function __construct(
        private readonly AppDashboardService $dashboard,
        private readonly AiShiftReadService $shifts,
        private readonly AiTaskService $tasks,
        private readonly AiShiftInsightsService $insights,
        private readonly AiAbsenceReadService $absences,
        private readonly AiRoomConflictService $conflicts,
    ) {
    }

    public function overview(User $user, CarbonImmutable $day): AiDayOverviewData
    {
        $limit = (int) config('ai.list_limit');

        return new AiDayOverviewData(
            date: $day->toDateString(),
            events: $this->events($user, $day, $limit),
            my_shifts: $this->shifts->schedule($user, $day->startOfDay(), $day->endOfDay(), $limit),
            my_tasks_due: $this->tasks->dueUntil($user, $day, self::TASK_LIMIT),
            planner: AiShiftPlanningAccess::mayPlanShifts($user) ? $this->planner($user, $day) : null,
        );
    }

    private function events(User $user, CarbonImmutable $day, int $limit): AiDayEventListData
    {
        $query = $this->dashboard->eventsOfDayQuery($user, Carbon::parse($day->toDateString()), true);
        $total = (clone $query)->count();
        $timezone = (string) config('app.timezone');

        $events = $query
            ->with(['project', 'room', 'event_type'])
            ->orderBy('start_time')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return new AiDayEventListData(
            data: $events->map(static fn (Event $event): AiDayEventData => new AiDayEventData(
                id: (int) $event->id,
                name: AiText::truncate($event->eventName ?: $event->name),
                start: $event->start_time?->copy()->setTimezone($timezone)->toIso8601String(),
                end: $event->end_time?->copy()->setTimezone($timezone)->toIso8601String(),
                all_day: (bool) $event->allDay,
                room: AiRefData::make($event->room_id, $event->room?->name),
                project: AiRefData::make($event->project_id, $event->project?->name),
                event_type: AiRefData::make($event->event_type_id, $event->event_type?->name),
            ))->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    private function planner(User $user, CarbonImmutable $day): AiDayPlannerData
    {
        $open = $this->insights->openShifts($user, $day, $day, null, (int) config('ai.list_limit'));
        $byCraft = [];

        foreach ($open->data as $shift) {
            if ($shift->craft === null) {
                continue;
            }

            $missing = array_sum(array_map(static fn ($demand): int => $demand->missing, $shift->open));
            $byCraft[$shift->craft->id] ??= new AiCraftMissingData($shift->craft, 0);
            $byCraft[$shift->craft->id]->missing += $missing;
        }

        $absent = $this->absences->entries($day, $day, null, null);
        [$conflicts, $conflictCount] = $this->conflicts->find($user, $day, $day, null, self::PLANNER_LIST_LIMIT);

        return new AiDayPlannerData(
            open_shifts: new AiDayOpenShiftsData(
                shifts: $open->summary->shifts,
                missing: $open->summary->missing,
                by_craft: array_values($byCraft),
            ),
            absences: new AiDayAbsencesData(
                count: count($absent),
                data: array_map(
                    static fn ($entry): AiDayAbsenceData => new AiDayAbsenceData(
                        new AiPersonRefData($entry->person->type, $entry->person->id, $entry->person->name),
                        $entry->absences[0]->type ?? AiAbsenceReadService::TYPE_VACATION,
                    ),
                    array_slice($absent, 0, self::PLANNER_LIST_LIMIT)
                ),
            ),
            room_conflicts: new AiDayRoomConflictsData($conflictCount, $conflicts),
        );
    }
}
