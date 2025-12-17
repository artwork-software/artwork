<?php

namespace Artwork\Modules\Task\Services;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Repositories\ChecklistRepository;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Task\Repositories\TaskRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly ChecklistRepository $checklistRepository
    ) {
    }
    public function createNewTask(array $attributes): Task
    {
        return new Task($attributes);
    }

    public function createTaskByRequest(
        Checklist $checklist,
        string $name,
        int $userId,
        ?string $description,
        ?string $deadline,
        ?array $userIds
    ): Task {
        /** @var Task $task */
        $task = $this->taskRepository->save(
            $this->createNewTask([
                'checklist_id' => $checklist->id,
                'name' => $name,
                'description' => $description,
                'deadline' => $deadline ? Carbon::parse($deadline)->endOfDay() : null,
                'done' => false,
                'order' => $this->checklistRepository->getOrderByChecklists($checklist),
            ])
        );


        // add all users to $checklist->project when they not in project
        if ($checklist->hasProject()) {
            foreach ($userIds as $userId) {
                if (!$checklist->project->users->contains($userId)) {
                    $checklist->project->users()->attach($userId);
                }
            }
        }

        // remove $userId from $userIds
        $userIds = array_diff($userIds, [$userId]);

        if ($userIds) {
            $this->taskRepository->syncWithDetach($task->task_users(), $userIds);
        }

        // add $userId to Task
        $task->task_users()->attach($userId);

        return $task;
    }

    public function deleteByChecklist(Checklist $checklist): void
    {
        $this->taskRepository->deleteByModel($checklist);
    }

    public function getByChecklist(Checklist $checklist): Collection
    {
        return $this->taskRepository->findByModel($checklist);
    }

    public function syncTaskUsersWithDetach(Task $task, array $ids): void
    {
        $this->taskRepository->syncWithDetach($task->task_users(), $ids);
    }

    public function deleteAll(Collection|array $tasks): void
    {
        /** @var Task $task */
        foreach ($tasks as $task) {
            $task->delete();
        }
    }

    public function restoreAll(Collection|array $tasks): void
    {
        /** @var Task $task */
        foreach ($tasks as $task) {
            $task->restore();
        }
    }

    public function forceDeleteAll(Collection|array $tasks): void
    {
        /** @var Task $task */
        foreach ($tasks as $task) {
            $task->forceDelete();
        }
    }

    public function restore(Task $task): void
    {
        $task->restore();
    }

    public function doneOrUndoneAllTasks(
        Checklist $checklist,
        bool $done,
        int $userId
    ): Collection {
        $tasks = $this->getByChecklist($checklist);

        /** @var Task $task */
        foreach ($tasks as $task) {
            $this->taskRepository->update(
                $task,
                [
                    'done' => $done,
                    'user_id' => $done ? $userId : null,
                    'done_at' => $done ? Carbon::now() : null
                ]
            );
        }

        return $tasks;
    }

    public function duplicateTasksByChecklist(Checklist $checklist, Checklist $newChecklist): void
    {
        $tasks = $this->getByChecklist($checklist);
        foreach ($tasks as $task) {
            $newTask = $task->replicate();
            $newTask->checklist_id = $newChecklist->id;
            $this->taskRepository->save($newTask);

            // Copy task_users relationship
            $newTask->task_users()->sync($task->task_users->pluck('id')->toArray());
        }
    }

    public function reorderTasks(SupportCollection $tasks): Task
    {
        $lastTask = null;
        foreach ($tasks as $task) {
            /** @var Task $taskObject */
            $taskObject = $this->taskRepository->findById($task['id']);
            $taskObject->order = $task['order'];
            /** @var Task $lastTask */
            $lastTask = $this->taskRepository->save($taskObject);
        }

        return $lastTask;
    }

    public function doneOrUndoneTask(Task $task, int $userId): Task
    {
        /** @var Task $task */
        $task->done = !$task->done;
        $task->user_id = $task->done ? $userId : null;
        $task->done_at = $task->done ? now() : null;
        $this->taskRepository->save($task);
        return $task;
    }

    public function updateByRequest(
        Task $task,
        SupportCollection $data,
    ): Task {
        $task->name = $data->get('name');
        $task->description = $data->get('description');
        $task->deadline = $data->get('deadlineDate') ? Carbon::parse($data->get('deadlineDate'))->endOfDay() : null;
        $task->done = false;
        $task->done_at = null;
        $task->user_id = null;
        $this->syncTaskUsers(
            $task,
            $data->get('users')
        );

        $checklist = $task->checklist;
        $userIds = $data->get('users');
        if ($checklist->hasProject()) {
            foreach ($userIds as $userId) {
                if (!$checklist->project->users->contains($userId)) {
                    $checklist->project->users()->attach($userId);
                }
            }
        }

        $this->taskRepository->save($task);
        return $task;
    }

    public function syncTaskUsers(Task $task, array $ids): void
    {
        $this->taskRepository->syncWithDetach($task->task_users(), $ids);
    }
}
