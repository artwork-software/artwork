<?php

namespace Artwork\Modules\Task\Services;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Task\Repositories\TaskRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class TaskService
{
    public function __construct(private TaskRepository $taskRepository)
    {
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
}
