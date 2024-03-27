<?php

namespace Artwork\Modules\Checklist\Services;

use App\Models\Task;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Checklist\Http\Requests\ChecklistUpdateRequest;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Repositories\ChecklistRepository;
use Artwork\Modules\Tasks\Services\TaskService;
use Illuminate\Database\Eloquent\Collection;

class ChecklistService
{
    public function __construct(
        private readonly ChecklistRepository $checklistRepository,
        private readonly TaskService $taskService
    ) {
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

    public function assignUsersById(Checklist $checklist, array $ids): void
    {
        $checklist->users()->sync($ids);
        $this->taskService->getByChecklist($checklist)->each(function (Task $task) use ($ids): void {
            $this->taskService->syncTaskUsersWithoutDetach($task, $ids);
        });
    }

    public function delete(Checklist $checklist): void
    {
        $this->taskService->deleteByChecklist($checklist);
        $this->checklistRepository->delete($checklist);
    }

    public function deleteAll(Collection|array $checklists): void
    {
        /** @var Checklist $checklist */
        foreach ($checklists as $checklist) {
            $this->taskService->deleteAll($checklist->tasks);
            $this->checklistRepository->delete($checklist);
        }
    }

    public function restoreAll(Collection|array $checklists): void
    {
        /** @var Checklist $checklist */
        foreach ($checklists as $checklist) {
            $checklist->restore();
            $this->taskService->restoreAll($checklist->tasks);
        }
    }

    public function forceDeleteAll(Collection|array $checklists): void
    {
        /** @var Checklist $checklist */
        foreach ($checklists as $checklist) {
            $this->taskService->forceDeleteAll($checklist->tasks);
            $this->checklistRepository->forceDelete($checklist);
        }
    }

    public function restore(Checklist $checklist): void
    {
        $checklist->restore();
        $this->taskService->restoreAll($checklist->tasks);
    }
}
