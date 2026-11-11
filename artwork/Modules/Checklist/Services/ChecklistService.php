<?php

namespace Artwork\Modules\Checklist\Services;

use Artwork\Modules\Project\Services\ProjectTeamService;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Checklist\Http\Requests\ChecklistUpdateRequest;
use Artwork\Modules\Checklist\Http\Resources\ChecklistIndexResource;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Checklist\Repositories\ChecklistRepository;
use Artwork\Modules\Checklist\Http\Resources\ChecklistTemplateIndexResource;
use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Task\Services\TaskService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Project\Services\ProjectTeamNotificationService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

readonly class ChecklistService
{
    public function __construct(
        private ChecklistRepository $checklistRepository,
        private ProjectTeamNotificationService $projectTeamNotificationService,
    ) {
    }

    public function updateByRequest(
        Checklist $checklist,
        ChecklistUpdateRequest $request,
        TaskService $taskService
    ): Checklist|Model {
        $checklist->fill($request->fillableFields());

        if ($request->get('tasks')) {
            $taskService->deleteByChecklist($checklist);
            $checklist->tasks()->delete();
            $checklist->tasks()->createMany(
                array_map(
                    static fn (array $task): array => array_intersect_key(
                        $task,
                        array_flip(['name', 'description', 'done', 'order', 'deadline'])
                    ),
                    (array) $request->input('tasks', [])
                )
            );
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
        $checklists = Checklist::with(['users', 'tasks.task_users', 'tasks.user_who_done', 'project'])
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
                            });
                            // Kein Zweig "Projektmitglied": der Filter unten verwarf genau diese Checklisten
                            // wieder – geladen wurden trotzdem alle Checklisten aller eigenen Projekte.
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
            // Keep if any task is assigned to the user
            if (
                $checklist->tasks->contains(function ($task) use ($userId) {
                    return $task->task_users->contains('id', $userId);
                })
            ) {
                return true;
            }
        });

        // Apply sorting
        $checklists = $this->applySorting($checklists, $sort);

        $publicChecklists = $checklists->where('private', false)->values();
        $privateChecklists = $checklists->where('private', true)->values();

        // Die Oberfläche zeigt öffentliche und private Listen in einer Liste: die Position hält die
        // Sortierung über beide Gruppen hinweg fest.
        $positions = $checklists->values()->pluck('id')->flip();
        $withSortPosition = static fn (array $resolvedChecklists): array => array_map(
            static fn (array $checklist): array => $checklist + ['sort_position' => $positions[$checklist['id']]],
            $resolvedChecklists
        );

        $openedChecklists = User::where('id', $userId)->first()?->opened_checklists ?? [];

        return [
            'opened_checklists' => $openedChecklists,
            'checklist_templates' => ChecklistTemplateIndexResource::collection(
                ChecklistTemplate::with(['task_templates', 'user', 'users'])->get()
            )->resolve(),
            'public_checklists' => $withSortPosition(
                ChecklistIndexResource::collection($publicChecklists)->resolve()
            ),
            'private_checklists' => $withSortPosition(
                ChecklistIndexResource::collection($privateChecklists)->resolve()
            ),
        ];
    }

    /**
     * Sortierung nach dem ersten Termin des Projekts. Die frühesten Starts kommen aus einer Abfrage; vorher
     * lud der Vergleicher bei jedem Vergleich zwei Termine (n·log n Abfragen).
     */
    private function applySorting(\Illuminate\Support\Collection $checklists, int $sort): \Illuminate\Support\Collection
    {
        if ($sort !== 1 && $sort !== 2) {
            return $checklists;
        }

        $projectIds = $checklists->pluck('project_id')->filter()->unique()->values();
        $firstStartByProject = $projectIds->isEmpty()
            ? collect()
            : Event::query()
                ->toBase()
                ->whereIn('project_id', $projectIds)
                ->groupBy('project_id')
                ->selectRaw('project_id, MIN(start_time) as first_start')
                ->pluck('first_start', 'project_id');

        $firstStart = static function (Checklist $checklist) use ($firstStartByProject): int {
            $start = $checklist->project_id !== null ? ($firstStartByProject[$checklist->project_id] ?? null) : null;

            return $start !== null ? (int) strtotime((string) $start) : PHP_INT_MAX;
        };

        $sorted = $checklists->sort(fn (Checklist $a, Checklist $b): int => $sort === 1
            ? $firstStart($a) <=> $firstStart($b)
            : $firstStart($b) <=> $firstStart($a));

        return $sorted->values();
    }

    public function assignUsersById(Checklist $checklist, array $ids): void
    {
        $checklist->users()->sync($ids);
        if ($checklist->hasProject()) {
            $project = $checklist->project;
            $addedUserIds = ProjectTeamService::addImplicitMembers($project, $ids);
            $this->projectTeamNotificationService->notifyAddedToTeam($project, $addedUserIds);
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

    /**
     * Stellt Checklisten samt ihren Aufgaben wieder her. Mit $deletedSince kommen nur die Aufgaben zurück,
     * die ab diesem Zeitpunkt (also zusammen mit der Checkliste) in den Papierkorb gingen.
     */
    public function restoreAll(
        Collection|array $checklists,
        TaskService $taskService,
        ?CarbonInterface $deletedSince = null
    ): void {
        /** @var Checklist $checklist */
        foreach ($checklists as $checklist) {
            $checklist->restore();
            $taskService->restoreAll(
                $checklist->tasks()
                    ->onlyTrashed()
                    ->when(
                        $deletedSince !== null,
                        static fn ($query) => $query->where('tasks.deleted_at', '>=', $deletedSince)
                    )
                    ->get()
            );
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
        $taskService->restoreAll($checklist->tasks()->onlyTrashed()->get());
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
