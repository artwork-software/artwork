<?php

namespace Artwork\Modules\Checklist\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Checklist\Http\Requests\ChecklistUpdateRequest;
use Artwork\Modules\Checklist\Http\Resources\ChecklistIndexResource;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Repositories\ChecklistRepository;
use Artwork\Modules\Checklist\Http\Resources\ChecklistTemplateIndexResource;
use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Task\Services\TaskService;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use stdClass;

readonly class ChecklistService
{
    public function __construct(private ChecklistRepository $checklistRepository)
    {
    }

    public function updateByRequest(
        Checklist $checklist,
        ChecklistUpdateRequest $request,
        TaskService $taskService
    ): Checklist|Model {
        $checklist->fill($request->all());

        if ($request->get('tasks')) {
            $taskService->deleteByChecklist($checklist);
            $checklist->tasks()->delete();
            $checklist->tasks()->createMany($request->tasks);
        }

        return $this->checklistRepository->save($checklist);
    }

    public function getChecklistsWithMyTask(
        int $userId,
        ProjectTabService $projectTabService,
        int $sort
    ): \Illuminate\Support\Collection {
        return $this->checklistRepository->getChecklistWhereHasTaskUsersWithFilteredTasks(
            $userId,
            $projectTabService,
            $sort
        );
    }

    public function getPrivateChecklists(int $userId, int $filter): Collection
    {
        $doneTask = false;
        if ($filter === 3) {
            $doneTask = true;
        }

        return $this->checklistRepository->getChecklistsForUserWithFilteredTasks($userId, $doneTask, $filter);
    }

    /**
     * Build the checklist payload for OwnTasksManagement using ChecklistIndexResource.
     * Returns public and private checklists in the same format as the project checklist endpoint.
     */
    public function buildOwnTasksChecklistPayload(int $userId, int $sort): array
    {
        $checklists = Checklist::with(['users', 'tasks.task_users', 'project'])
            ->where(function ($query) use ($userId): void {
                $query->where(function ($q) use ($userId): void {
                    // Public checklists where user is assigned, has tasks, or is a project member
                    $q->where('private', false)
                        ->where(function ($inner) use ($userId): void {
                            $inner->whereHas('users', function ($uq) use ($userId): void {
                                $uq->where('user_id', $userId);
                            })
                            ->orWhereHas('tasks.task_users', function ($tq) use ($userId): void {
                                $tq->where('user_id', $userId);
                            })
                            ->orWhereHas('project.users', function ($pq) use ($userId): void {
                                $pq->where('users.id', $userId);
                            });
                        });
                })
                ->orWhere(function ($q) use ($userId): void {
                    // Private checklists owned by the user
                    $q->where('private', true)
                        ->where('user_id', $userId);
                });
            })
            ->get();

        // Filter out checklists that have no tasks assigned to the user AND are not assigned to the user
        $checklists = $checklists->filter(function ($checklist) use ($userId) {
            // Private checklists owned by user - always keep
            if ($checklist->private && $checklist->user_id === $userId) {
                return true;
            }
            // Keep if checklist is assigned to the user
            if ($checklist->users->contains('id', $userId)) {
                return true;
            }
            // Keep if checklist has at least one task assigned to the user
            return $checklist->tasks->contains(function ($task) use ($userId) {
                return $task->task_users->pluck('id')->contains($userId);
            });
        });

        // Apply sorting
        $checklists = $this->applySorting($checklists, $sort);

        $publicChecklists = $checklists->where('private', false)->values();
        $privateChecklists = $checklists->where('private', true)->values();

        $openedChecklists = User::where('id', $userId)->first()?->opened_checklists ?? [];

        return [
            'opened_checklists' => $openedChecklists,
            'checklist_templates' => ChecklistTemplateIndexResource::collection(
                ChecklistTemplate::all()
            )->resolve(),
            'public_checklists' => ChecklistIndexResource::collection($publicChecklists)->resolve(),
            'private_checklists' => ChecklistIndexResource::collection($privateChecklists)->resolve(),
        ];
    }

    private function applySorting(\Illuminate\Support\Collection $checklists, int $sort): \Illuminate\Support\Collection
    {
        switch ($sort) {
            case 1:
                return $checklists->sort(function ($a, $b) {
                    $aStart = $a->project?->events()->orderBy('start_time', 'ASC')->first();
                    $bStart = $b->project?->events()->orderBy('start_time', 'ASC')->first();
                    $aTime = $aStart ? strtotime($aStart->start_time) : PHP_INT_MAX;
                    $bTime = $bStart ? strtotime($bStart->start_time) : PHP_INT_MAX;
                    return $aTime - $bTime;
                })->values();
            case 2:
                return $checklists->sort(function ($a, $b) {
                    $aStart = $a->project?->events()->orderBy('start_time', 'ASC')->first();
                    $bStart = $b->project?->events()->orderBy('start_time', 'ASC')->first();
                    $aTime = $aStart ? strtotime($aStart->start_time) : PHP_INT_MAX;
                    $bTime = $bStart ? strtotime($bStart->start_time) : PHP_INT_MAX;
                    return $bTime - $aTime;
                })->values();
            default:
                return $checklists;
        }
    }

    public function assignUsersById(Checklist $checklist, array $ids): void
    {
        $checklist->users()->sync($ids);
        if ($checklist->hasProject()) {
            $project = $checklist->project;
            foreach ($ids as $id) {
                if (!$project->users->contains($id)) {
                    $project->users()->attach($id);
                }
            }
        }
    }

    public function delete(Checklist $checklist, TaskService $taskService): void
    {
        $taskService->deleteByChecklist($checklist);
        $this->checklistRepository->delete($checklist);
    }

    public function deleteAll(Collection|array $checklists, TaskService $taskService): void
    {
        /** @var Checklist $checklist */
        foreach ($checklists as $checklist) {
            $taskService->deleteAll($checklist->tasks);
            $this->checklistRepository->delete($checklist);
        }
    }

    public function restoreAll(Collection|array $checklists, TaskService $taskService): void
    {
        /** @var Checklist $checklist */
        foreach ($checklists as $checklist) {
            $checklist->restore();
            $taskService->restoreAll($checklist->tasks);
        }
    }

    public function forceDeleteAll(Collection|array $checklists, TaskService $taskService): void
    {
        /** @var Checklist $checklist */
        foreach ($checklists as $checklist) {
            $tasks = Task::onlyTrashed()->where('checklist_id', $checklist->id)->get();
            $taskService->forceDeleteAll($tasks);
            $this->checklistRepository->forceDelete($checklist);
        }
    }

    public function restore(Checklist $checklist, TaskService $taskService): void
    {
        $checklist->restore();
        $taskService->restoreAll($checklist->tasks);
    }

    public function getProjectChecklists(
        Project $project,
        stdClass $headerObject,
        ComponentInTab $componentInTab
    ): stdClass {
        $headerObject->project->opened_checklists = User::where('id', Auth::id())
            ->first()->opened_checklists;
        $headerObject->project->checklist_templates = ChecklistTemplateIndexResource::collection(
            ChecklistTemplate::all()
        )->resolve();
        $userId = Auth::id();
        $headerObject->project->public_checklists = ChecklistIndexResource::collection(
            $project->checklists
                ->whereIn('tab_id', $componentInTab->scope)
                ->where('private', false)
                ->filter(function ($checklist) use ($userId) {
                    // Prüfen, ob der Benutzer in den Checklistenbenutzern ist
                    $isInChecklistUsers = $checklist->users->contains('id', $userId);

                    // Prüfen, ob der Benutzer in den Aufgabenbenutzern ist
                    $isInTaskUsers = $checklist->tasks->contains(function ($task) use ($userId) {
                        return $task->task_users->contains('id', $userId);
                    });

                    // Prüfen, ob der Benutzer der Ersteller der Checkliste ist
                    $isCreator = $checklist->user_id === $userId;

                    //Prüfen, ob der Benutzer im Projektteam ist
                    $isInProjectTeam = $checklist->project->users->contains('id', $userId);

                    return $isInChecklistUsers || $isInTaskUsers || $isCreator || $isInProjectTeam;
                })
        );
        $headerObject->project->private_checklists = ChecklistIndexResource::collection(
            $project->checklists
                ->whereIn('tab_id', $componentInTab->scope)
                ->where('private', true)
                ->filter(function ($checklist) use ($userId) {
                    // Prüfen, ob der Benutzer in den Checklistenbenutzern ist
                    $isInChecklistUsers = $checklist->users->contains('id', $userId);

                    // Prüfen, ob der Benutzer in den Aufgabenbenutzern ist
                    $isInTaskUsers = $checklist->tasks->contains(function ($task) use ($userId) {
                        return $task->task_users->contains('id', $userId);
                    });

                    // Prüfen, ob der Benutzer der Ersteller der Checkliste ist
                    $isCreator = $checklist->user_id === $userId;

                    //Prüfen, ob der Benutzer im Projektteam ist
                    $isInProjectTeam = $checklist->project->users->contains('id', $userId);

                    return $isInChecklistUsers || $isInTaskUsers || $isCreator || $isInProjectTeam;
                })
        );

        return $headerObject;
    }

    public function getProjectChecklistsAll(Project $project, stdClass $headerObject): stdClass
    {
        $headerObject->project->opened_checklists = User::where('id', Auth::id())
            ->first()->opened_checklists;
        $userId = Auth::id();
        $headerObject->project->public_all_checklists = ChecklistIndexResource::collection(
            $project->checklists
                ->where('private', false)
                ->filter(function ($checklist) use ($userId) {
                    $isInChecklistUsers = $checklist->users->contains('id', $userId);
                    $isInTaskUsers = $checklist->tasks->contains(function ($task) use ($userId) {
                        return $task->task_users->contains('id', $userId);
                    });
                    $isCreator = $checklist->user_id === $userId;
                    $isInProjectTeam = $checklist->project->users->contains('id', $userId);
                    return $isInChecklistUsers || $isInTaskUsers || $isCreator || $isInProjectTeam;
                })
        )->resolve();
        $headerObject->project->private_all_checklists = ChecklistIndexResource::collection(
            $project->checklists
                ->where('private', true)
                ->filter(function ($checklist) use ($userId) {
                    $isInChecklistUsers = $checklist->users->contains('id', $userId);
                    $isInTaskUsers = $checklist->tasks->contains(function ($task) use ($userId) {
                        return $task->task_users->contains('id', $userId);
                    });
                    $isCreator = $checklist->user_id === $userId;
                    $isInProjectTeam = $checklist->project->users->contains('id', $userId);
                    return $isInChecklistUsers || $isInTaskUsers || $isCreator || $isInProjectTeam;
                })
        )->resolve();
        return $headerObject;
    }

    public function createNewChecklist(array $attributes): Checklist
    {
        return new Checklist($attributes);
    }

    public function duplicate(
        Checklist $checklist
    ): Checklist {
        return $this->createNewChecklist([
            'name' => $checklist->name . ' (copy)',
            'project_id' => $checklist->project_id,
            'user_id' => $checklist->user_id,
            'tab_id' => $checklist->tab_id
        ]);
    }

    public function getById(int $id): Checklist|null
    {
        return $this->checklistRepository->getById($id);
    }
}
