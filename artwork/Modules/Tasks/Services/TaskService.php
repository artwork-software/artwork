<?php

namespace Artwork\Modules\Tasks\Services;

use App\Models\Task;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Tasks\Repositories\TaskRepository;
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

    public function syncTaskUsersWithoutDetach(Task $task, array $ids): void
    {
        $this->taskRepository->syncWithoutDetach($task->task_users(), $ids);
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
