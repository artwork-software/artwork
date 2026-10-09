<?php

namespace Artwork\Modules\AppApi\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AppDashboardService
{
    public function __construct(
        private readonly CarbonService $carbonService,
        private readonly AppShiftPlanService $shiftPlanService,
    ) {
    }

    /**
     * The app home screen payload: the same per-user blocks as the web
     * dashboard (EventController::showDashboardPage), without its modal/edit
     * support props. `today` reuses the shift plan day shape so the app renders
     * it with the same component as the Einsatzplan screen.
     *
     * @return array<string, mixed>
     */
    public function getDashboard(User $user): array
    {
        $today = $this->carbonService->getNow();

        $todayBlock = $this->shiftPlanService->getDays(
            $user,
            $today->copy()->startOfDay(),
            $today->copy()->endOfDay(),
        )[0];
        $eventsToday = $this->getEventsOfToday($user, $today);

        return [
            'date' => $today->format('Y-m-d'),
            'today' => $todayBlock,
            'events_today' => $eventsToday,
            // Server-derived so the app never re-implements the overnight rule
            // or merges/sorts the two lists itself.
            'next_shift' => $this->nextShift($todayBlock['shifts'], $today),
            'timeline' => $this->timeline($todayBlock['shifts'], $eventsToday),
            'tasks' => $this->getOpenTasks($user),
            'unread_notifications' => $user->notifications()
                ->whereDate('created_at', $today->format('Y-m-d'))
                ->whereNull('read_at')
                ->count(),
        ];
    }

    /**
     * The first of today's shifts that has not ended yet — an overnight shift
     * (end before start) ends tomorrow and therefore still counts.
     *
     * @param array<int, array<string, mixed>> $shifts mapped plan-day shifts
     * @return array<string, mixed>|null
     */
    private function nextShift(array $shifts, Carbon $now): ?array
    {
        usort($shifts, static fn (array $a, array $b): int => strcmp((string) $a['start'], (string) $b['start']));

        foreach ($shifts as $shift) {
            $end = Carbon::parse($now->format('Y-m-d') . ' ' . $shift['end']);
            if ($shift['end'] < $shift['start']) {
                $end->addDay();
            }

            if ($end->greaterThanOrEqualTo($now)) {
                return $shift;
            }
        }

        return null;
    }

    /**
     * Today's shifts and events as one chronological list: all-day events
     * first, then by start time.
     *
     * @param array<int, array<string, mixed>> $shifts
     * @param array<int, array<string, mixed>> $events
     * @return array<int, array<string, mixed>>
     */
    private function timeline(array $shifts, array $events): array
    {
        $rows = [];
        foreach ($events as $event) {
            $startTime = $event['start_time'] === null
                ? null
                : Carbon::parse($event['start_time'])->format('H:i');
            $rows[] = [
                'type' => 'event',
                'sort' => $startTime ?? '',
                'shift' => null,
                'event' => $event,
            ];
        }
        foreach ($shifts as $shift) {
            $rows[] = [
                'type' => 'shift',
                'sort' => (string) $shift['start'],
                'shift' => $shift,
                'event' => null,
            ];
        }

        usort($rows, static fn (array $a, array $b): int => strcmp($a['sort'], $b['sort']));

        return array_map(static function (array $row): array {
            unset($row['sort']);

            return $row;
        }, $rows);
    }

    /**
     * Today's events of projects the user works in — same query as the web
     * dashboard's `eventsOfDay`.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getEventsOfToday(User $user, Carbon $today): array
    {
        return Event::query()
            ->whereBetween('start_time', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->whereHas('project.users', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->with(['project', 'room', 'event_type'])
            ->get()
            ->map(static fn (Event $event): array => [
                'id' => $event->id,
                'name' => $event->eventName,
                'start_time' => $event->start_time?->toIso8601String(),
                'end_time' => $event->end_time?->toIso8601String(),
                'event_type' => $event->event_type === null ? null : [
                    'id' => $event->event_type->id,
                    'name' => $event->event_type->name,
                    'abbreviation' => $event->event_type->abbreviation,
                    'hex_code' => $event->event_type->hex_code,
                ],
                'project' => $event->project === null ? null : [
                    'id' => $event->project->id,
                    'name' => $event->project->name,
                ],
                'room' => $event->room === null ? null : [
                    'id' => $event->room->id,
                    'name' => $event->room->name,
                ],
            ])
            ->all();
    }

    /**
     * The user's five most urgent open tasks — same query as the web
     * dashboard's `tasks`.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getOpenTasks(User $user): array
    {
        return Task::query()
            ->where('done', false)
            ->where(function (Builder $query) use ($user): void {
                $query->whereHas('checklist', function (Builder $checklistBuilder) use ($user): void {
                    $checklistBuilder->where('user_id', $user->id);
                })->orWhereHas('task_users', function (Builder $userBuilder) use ($user): void {
                    $userBuilder->where('user_id', $user->id);
                });
            })
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END, deadline ASC')
            ->limit(5)
            ->with(['checklist.project'])
            ->get()
            ->map(static fn (Task $task): array => [
                'id' => $task->id,
                'name' => $task->name,
                'description' => $task->description,
                'deadline' => $task->deadline ? Carbon::parse($task->deadline)->toIso8601String() : null,
                'checklist_name' => $task->checklist?->name,
                'project_name' => $task->checklist?->project?->name,
            ])
            ->all();
    }
}
