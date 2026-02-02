<?php

namespace App\Http\Controllers;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Checklist\Events\ChecklistUpdated;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\MoneySource\Services\MoneySourceTaskService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\Task\Http\Requests\FilterOwnTasksRequest;
use Artwork\Modules\Task\Http\Requests\StoreTaskRequest;
use Artwork\Modules\Task\Http\Requests\UpdateTaskOrderInChecklistRequest;
use Artwork\Modules\Task\Http\Requests\UpdateTaskRequest;
use Artwork\Modules\Task\Http\Resources\TaskIndexResource;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Task\Services\TaskService;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class TaskController extends Controller
{
    public function __construct(
        private readonly ChangeService $changeService,
        private readonly SchedulingService $schedulingService,
        private readonly TaskService $taskService,
        private readonly ChecklistService $checklistService,
        private readonly AuthManager $authManager,
        private readonly MoneySourceTaskService $moneySourceTaskService,
    ) {
    }

    public function create(): Response|ResponseFactory
    {
        return inertia('Tasks/Create');
    }

    public function indexOwnTasks(
        ProjectTabService $projectTabService,
        FilterOwnTasksRequest $request
    ): Response|ResponseFactory {

        $checklists = $this->checklistService->getChecklistsWithMyTask(
            $this->authManager->id(),
            $projectTabService,
            $request->integer('filter'),
        );


        $privateChecklists = $this->checklistService->getPrivateChecklists(
            $this->authManager->id(),
            $request->integer('filter')
        );

        $moneySourceTasks = $this->moneySourceTaskService->getMyMoneySourceTasks(
            $this->authManager->id(),
            $request->integer('filter')
        );

        return inertia('Tasks/OwnTasksManagement', [
            'checklists' => $checklists,
            'money_source_task' => $moneySourceTasks,
            'first_project_tasks_tab_id' => $projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                ProjectTabComponentEnum::CHECKLIST
            ),
            'checklist_templates' => ChecklistTemplate::all()
        ]);
    }

    public function store(
        StoreTaskRequest $request
    ): JsonResponse|RedirectResponse {
        /** @var Checklist $checklist */
        $checklist = $this->checklistService->getById($request->integer('checklist_id'));

        $this->taskService->createTaskByRequest(
            $checklist,
            $request->string('name'),
            $this->authManager->id(),
            $request->string('description'),
            $request->string('deadlineDate'),
            $request->collect('users')->toArray(),
        );

        if ($checklist->hasProject()) {
            // add users to project if they are not already there
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($checklist->project_id)
                    ->setTranslationKey('Task added to')
                    ->setTranslationKeyPlaceholderValues([
                        $request->name,
                        $checklist->name
                    ])
            );
            broadcast(new ChecklistUpdated($checklist->project_id))->toOthers();
        }

        $this->createNotificationForAllChecklistUser($checklist);

        return Redirect::back();
    }

    private function createNotificationForAllChecklistUser(Checklist $checklist): void
    {
        $uniqueTaskUsers = [];
        if ($checklist->user_id === null) {
            $checklistUsers = $checklist->users()->get();
            foreach ($checklistUsers as $checklistUser) {
                $uniqueTaskUsers[$checklistUser->id] = $checklistUser->id;
            }
            foreach ($uniqueTaskUsers as $uniqueTaskUser) {
                $this->schedulingService->create($uniqueTaskUser, 'TASK_ADDED', 'TASKS', $checklist->id);
            }
        } else {
            $this->schedulingService->create(Auth::id(), 'TASK_ADDED', 'TASKS', $checklist->id);
        }
    }


    public function edit(Task $task): Response|ResponseFactory
    {
        return inertia('Tasks/Edit', [
            'task' => new TaskIndexResource($task),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->taskService->updateByRequest(
            $task,
            $request->collect()
        );

        if ($checklist = $task->checklist()->first()) {
            if ($checklist->hasProject()) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Project::class)
                        ->setModelId($checklist->project_id)
                        ->setTranslationKey('Task modified in')
                        ->setTranslationKeyPlaceholderValues([
                            $task->name,
                            $checklist->name
                        ])
                );
                broadcast(new ChecklistUpdated($checklist->project_id))->toOthers();
            }

            $this->createNotificationUpdateTask($task);
        }

        return Redirect::back();
    }

    private function createNotificationUpdateTask(Task $task): void
    {
        $taskUser = $task->checklist()->first()->user_id;
        $users = $task->task_users()->get();
        $uniqueTaskUsers = [];
        if ($taskUser === null) {
            foreach ($users as $user) {
                $uniqueTaskUsers[$user->id] = $user->id;
            }
            foreach ($uniqueTaskUsers as $uniqueTaskUser) {
                $this->schedulingService->create($uniqueTaskUser, 'TASK_CHANGES', 'TASKS', $task->id);
            }
        } else {
            $this->schedulingService->create($taskUser, 'TASK_CHANGES', 'TASKS', $task->id);
        }
    }

    public function updateOrder(UpdateTaskOrderInChecklistRequest $request): RedirectResponse
    {
        $this->taskService->reorderTasks(
            $request->collect('checklistTasks')
        );

        return Redirect::back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        /** @var Checklist $checklist */
        $checklist = $task->checklist()->first();

        if ($checklist->hasProject()) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($checklist->project_id)
                    ->setTranslationKey('Task deleted from')
                    ->setTranslationKeyPlaceholderValues([
                        $task->name,
                        $checklist->name
                    ])
            );
            broadcast(new ChecklistUpdated($checklist->project_id))->toOthers();
        }

        $task->forceDelete();

        return Redirect::back();
    }

    public function updateDoneStatus(Task $task): RedirectResponse
    {
        $this->taskService->doneOrUndoneTask(
            $task,
            $this->authManager->id()
        );

        $checklist = $task->checklist()->first();
        if ($checklist && $checklist->hasProject()) {
            broadcast(new ChecklistUpdated($checklist->project_id))->toOthers();
        }

        return Redirect::back();
    }

    public function changeTaskChecklist(Checklist $checklist, Task $task): void
    {
        $oldChecklist = $task->checklist()->first();
        $task->update([
            'checklist_id' => $checklist->id
        ]);

        // Broadcast for old checklist's project
        if ($oldChecklist && $oldChecklist->hasProject()) {
            broadcast(new ChecklistUpdated($oldChecklist->project_id))->toOthers();
        }

        // Broadcast for new checklist's project
        if ($checklist->hasProject()) {
            broadcast(new ChecklistUpdated($checklist->project_id))->toOthers();
        }
    }
}
