<?php

namespace Artwork\Modules\Checklist\Services;

use App\Http\Resources\ChecklistIndexResource;
use App\Http\Resources\ChecklistTemplateIndexResource;
use App\Models\ChecklistTemplate;
use App\Models\Task;
use App\Models\User;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Checklist\Http\Requests\ChecklistUpdateRequest;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Repositories\ChecklistRepository;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ProjectTab\Models\ComponentInTab;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Artwork\Modules\Tasks\Services\TaskService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use stdClass;

readonly class ChecklistService
{
    public function __construct(
        private ChecklistRepository $checklistRepository,
        private TaskService $taskService
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
        $headerObject->project->public_checklists = ChecklistIndexResource::collection(
            $project->checklists->whereNull('user_id')->whereIn('tab_id', $componentInTab->scope)
        )
            ->resolve();
        $headerObject->project->private_checklists = ChecklistIndexResource::collection(
            $project->checklists->where('user_id', Auth::id())->whereIn('tab_id', $componentInTab->scope)
        )->resolve();
        return $headerObject;
    }

    public function getProjectChecklistsAll(Project $project, stdClass $headerObject): stdClass
    {
        $headerObject->project->opened_checklists = User::where('id', Auth::id())
            ->first()->opened_checklists;
        $headerObject->project->checklist_templates = ChecklistTemplateIndexResource::collection(
            ChecklistTemplate::all()
        )->resolve();
        $headerObject->project->public_all_checklists = ChecklistIndexResource::collection(
            $project->checklists->whereNull('user_id')
        )
            ->resolve();
        $headerObject->project->private_all_checklists = ChecklistIndexResource::collection(
            $project->checklists->where('user_id', Auth::id())
        )->resolve();
        return $headerObject;
    }
}
