<?php

namespace Artwork\Modules\Tasks\Services;

use App\Models\Task;
use Artwork\Modules\Checklist\Http\Requests\ChecklistUpdateRequest;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Repositories\ChecklistRepository;
use Artwork\Modules\Tasks\Repositories\TaskRepository;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    public function __construct(private readonly TaskRepository $taskRepository)
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
}
