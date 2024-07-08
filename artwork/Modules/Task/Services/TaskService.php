<?php

namespace Artwork\Modules\Task\Services;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Task\Repositories\TaskRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

readonly class TaskService
{
    public function __construct(private TaskRepository $taskRepository)
    {
    }
    public function createNewTaskObject(array $attributes): Task
    {
        return new Task($attributes);
    }

    public function createTaskByRequest(Checklist $checklist, SupportCollection $data): Task
    {
        $task = $this->createNewTaskObject([
            'checklist_id' => $checklist->id,
            'name' => $data->get('name'),
            'description' => $data->get('description'),
            'deadline' => Carbon::parse($data->get('deadline'))->endOfDay(),
            'done' => false,
            'order' => $checklist->tasks()->max('order') + 1,
        ]);
        $createdTask = $this->taskRepository->save($task);
        /** @var Task $createdTask */
        $createdTask->task_users()->sync($data->get('users'));

        return $createdTask;
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
    ): Task|null {
        $tasks = $this->getByChecklist($checklist);
        $lastTask = null;
        foreach ($tasks as $task) {
            $task->done = $done;
            if ($done) {
                $task->user_id = $userId;
                $task->done_at = now();
            } else {
                $task->user_id = null;
                $task->done_at = null;
            }
            /* @var Task $lastTask */
            $lastTask = $this->taskRepository->save($task);
        }

        return $lastTask;
    }

    public function duplicateTasksByChecklist(Checklist $checklist, Checklist $newChecklist): void
    {
        $tasks = $this->getByChecklist($checklist);
        foreach ($tasks as $task) {
            $newTask = $task->replicate();
            $newTask->checklist_id = $newChecklist->id;
            $this->taskRepository->save($newTask);
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
        $task->deadline = Carbon::parse($data->get('deadline'))->endOfDay();
        $task->done = false;
        $task->done_at = null;
        $task->user_id = null;
        $this->syncTaskUsers(
            $task,
            $data->get('users')
        );

        $this->taskRepository->save($task);
        return $task;
    }

    public function syncTaskUsers(Task $task, array $ids): void
    {
        $task->task_users()->sync($ids);
    }
}
