<?php

namespace Artwork\Modules\Checklist\Services;

use App\Models\Task;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Checklist\Http\Requests\ChecklistUpdateRequest;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Repositories\ChecklistRepository;
use Artwork\Modules\Tasks\Services\TaskService;

class ChecklistService
{
    public function __construct(private readonly ChecklistRepository $checklistRepository, private readonly TaskService $taskService)
    {

    }

    public function updateByRequest(Checklist $checklist, ChecklistUpdateRequest $request): Checklist|Model
    {
        $checklist->fill($request->data());

        if ($request->get('tasks')) {
            $this->taskService->deleteByChecklist($checklist);
            $checklist->tasks()->delete();
            $checklist->tasks()->createMany($request->tasks);
        }

        return $this->checklistRepository->save($checklist);
    }

    public function assignUsersById(Checklist $checklist,array $ids): void
    {
        $checklist->users()->sync($ids);
        $this->taskService->getByChecklist($checklist)->each(function(Task $task) use ($ids){
            $this->taskService->syncTaskUsersWithoutDetach($task, $ids);
        });
    }
}
