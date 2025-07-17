<?php

namespace Artwork\Modules\Checklist\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Task\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ChecklistRepository extends BaseRepository
{
    public function getById(int $id): mixed
    {
        return Checklist::find($id);
    }

    public function getOrderByChecklists(Checklist $checklist): int
    {
        return $checklist->tasks()->max('order') + 1;
    }


    public function getChecklistWhereHasTaskUsersWithFilteredTasks(
        int $userId,
        ProjectTabService $projectTabService,
        int $filter = 0
    ): array|\Illuminate\Support\Collection {
        $checklists = $this->getChecklistsForUser($userId);

        // Wende den entsprechenden Filter an
        switch ($filter) {
            case 1:
                $checklists = $this->sortChecklistsByProjectTime($checklists, 'asc');
                break;
            case 2:
                $checklists = $this->sortChecklistsByProjectTime($checklists, 'desc');
                break;
            case 3:
                $checklists = $this->sortChecklistsByName($checklists, 'asc');
                break;
            case 4:
                $checklists = $this->sortChecklistsByName($checklists, 'desc');
                break;
            case 5:
                $checklists = $this->sortChecklistsByTaskDeadline($checklists, 'asc');
                break;
            case 6:
                $checklists = $this->sortChecklistsByTaskDeadline($checklists, 'desc');
                break;
        }

        return $checklists->map(function (Checklist $checklist) use ($projectTabService) {
            $sortedTasks = $this->sortTasks($checklist->tasks);

            return $this->formatChecklist($checklist, $sortedTasks, $projectTabService);
        });
    }

    /**
     * Holen Sie die Checklisten, in denen der Benutzer entweder als Checklistenbenutzer oder Aufgabenbenutzer vorkommt.
     */
    private function getChecklistsForUser(int $userId)
    {
        return Checklist::with(['tasks'])
            ->where(function ($query) use ($userId): void {
                $query->whereHas('users', function ($userQuery) use ($userId): void {
                    $userQuery->where('user_id', $userId);
                })
                    ->orWhereHas('tasks.task_users', function ($taskUserQuery) use ($userId): void {
                        $taskUserQuery->where('user_id', $userId);
                    });
            })
            ->get()
            ->flatMap(function ($checklist) use ($userId) {
                if ($checklist->users->contains('id', $userId)) {
                    return [$checklist];
                }

                $filteredTasks = $checklist->tasks->filter(function ($task) use ($userId) {
                    return $task->task_users->contains('id', $userId);
                });

                if ($filteredTasks->isNotEmpty()) {
                    $checklist->setRelation('tasks', $filteredTasks);
                    return [$checklist];
                }

                return [];
            });
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.TooHigh, Generic.Metrics.CyclomaticComplexity.TooHigh
    private function sortChecklistsByProjectTime($checklists, $direction = 'asc')
    {
        return $checklists->sort(function ($a, $b) use ($direction) {
            $aFirstEvent = $a->project?->events()->orderBy('start_time', 'ASC')->first();
            $bFirstEvent = $b->project?->events()->orderBy('start_time', 'ASC')->first();

            $aLastEvent = $a->project?->events()->orderBy('end_time', 'DESC')->first();
            $bLastEvent = $b->project?->events()->orderBy('end_time', 'DESC')->first();

            $aStartTime = $aFirstEvent ? strtotime($aFirstEvent->start_time) : PHP_INT_MAX;
            $bStartTime = $bFirstEvent ? strtotime($bFirstEvent->start_time) : PHP_INT_MAX;

            $aEndTime = $aLastEvent ? strtotime($aLastEvent->end_time) : PHP_INT_MAX;
            $bEndTime = $bLastEvent ? strtotime($bLastEvent->end_time) : PHP_INT_MAX;

            $comparison = $aStartTime - $bStartTime;
            if ($comparison === 0) {
                $comparison = $aEndTime - $bEndTime;
            }

            return $direction === 'asc' ? $comparison : -$comparison;
        })->values();
    }

    /**
     * Sortieren Sie die Checklisten nach Namen.
     */
    private function sortChecklistsByName($checklists, $direction = 'asc')
    {
        return $checklists->sortBy(function ($checklist) use ($direction) {
            return $direction === 'asc' ? $checklist->name : -strcasecmp($checklist->name, $checklist->name);
        })->values();
    }

    /**
     * Sortieren Sie die Checklisten nach Task-Deadline.
     */
    private function sortChecklistsByTaskDeadline($checklists, $direction = 'asc')
    {
        return $checklists->sortBy(function ($checklist) use ($direction) {
            $earliestDeadline = $checklist->tasks->whereNotNull('deadline')->min('deadline');
            return $earliestDeadline ?
                strtotime($earliestDeadline) :
                ($direction === 'asc' ? PHP_INT_MAX : PHP_INT_MIN);
        })->values();
    }

    /**
     * Sortieren Sie die Aufgaben nach Deadline und erledigtem Status.
     */
    private function sortTasks($tasks)
    {
        $partitioned = $tasks->partition(function ($task) {
            return !$task->done;
        });

        $notDoneTasks = $partitioned[0]->sortBy(function ($task) {
            return $task->deadline ? $task->deadline->timestamp : PHP_INT_MAX;
        })->values();

        $doneTasks = $partitioned[1]->values();

        return $notDoneTasks->merge($doneTasks);
    }

    /**
     * @return array<string, mixed>
     */
    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.TooHigh, Generic.Metrics.CyclomaticComplexity.TooHigh
    private function formatChecklist(Checklist $checklist, $sortedTasks, ProjectTabService $projectTabService): array
    {
        return [
            'id' => $checklist->id,
            'name' => $checklist->name,
            'private' => $checklist->private,
            'showContent' => true,
            'users' => $checklist->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo_url,
                ];
            }),
            'hasProject' => $checklist->hasProject(),
            'project' => [
                'id' => $checklist?->project?->id,
                'name' => $checklist?->project?->name,
                'users' => $checklist?->project?->users,
                'checklist_tab_id' => $projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                    ProjectTabComponentEnum::CHECKLIST
                ),
                'firstEventInProject' => $checklist?->project?->events()->orderBy('start_time', 'ASC')->first(),
                'lastEventInProject' => $checklist?->project?->events()->orderBy('end_time', 'DESC')->first(),
            ],
            'checklist_tab_id' => $checklist->tab_id,
            'tasks' => $sortedTasks->map(function (Task $task) {
                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'deadline' => $task->deadline ? Carbon::parse($task->deadline)->format('d.m.Y, H:i') : null,
                    'deadlineDate' => $task->deadline ? Carbon::parse($task->deadline)->format('Y-m-d') : null,
                    'deadlineTime' => $task->deadline ? Carbon::parse($task->deadline)->format('H:i') : null,
                    'deadline_dt_local' => $task->deadline ?
                        Carbon::parse($task->deadline)->toDateTimeLocalString() : null,
                    'order' => $task->order,
                    'done' => $task->done,
                    'done_by_user' => $task->user_who_done,
                    'done_at' => $task->done_at ? Carbon::parse($task->done_at)->format('d.m.Y, H:i') : null,
                    'done_at_dt_local' => $task->done_at ?
                        Carbon::parse($task->done_at)->toDateTimeLocalString() :
                        null,
                    'users' => $task->task_users,
                    'formatted_dates' => $task->getFormattedDates(),
                ];
            }),
        ];
    }

    public function getChecklistsForUserWithFilteredTasks(int $userId, bool $doneTask, int $filter): Collection
    {
        return Checklist::query()
            ->where('user_id', $userId)
            ->with(['tasks' => function ($q) use ($doneTask, $filter): void {
                $q->where('done', $doneTask)
                    ->when($filter === 1, function ($q): void {
                        $q->orderBy('order');
                    })
                    ->when($filter === 2, function ($q): void {
                        $q->orderBy('deadline');
                    });
            }])
            ->get();
    }
}
