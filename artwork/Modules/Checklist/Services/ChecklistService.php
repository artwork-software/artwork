<?php

namespace Artwork\Modules\Checklist\Services;

use App\Http\Resources\ChecklistIndexResource;
use App\Http\Resources\ChecklistTemplateIndexResource;
use App\Models\ChecklistTemplate;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Support\Services\NewHistoryService;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Checklist\Http\Requests\ChecklistUpdateRequest;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Repositories\ChecklistRepository;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectHistory;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Artwork\Modules\Tasks\Services\TaskService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use stdClass;

class ChecklistService
{
    public function __construct(
        private readonly ChecklistRepository $checklistRepository,
        private readonly TaskService         $taskService,
        private readonly NewHistoryService   $historyService,
        private readonly ProjectService      $projectService
    )
    {
        $this->historyService->setModel(Checklist::class);
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

    public function getProjectChecklists(Project $project, stdClass $headerObject, ProjectTab $projectTab): stdClass
    {
        $headerObject->project->opened_checklists = User::where('id', Auth::id())
            ->first()->opened_checklists;
        $headerObject->project->checklist_templates = ChecklistTemplateIndexResource::collection(
            ChecklistTemplate::all()
        )->resolve();
        $headerObject->project->public_checklists = ChecklistIndexResource::collection(
            $project->checklists->whereNull('user_id')->where('tab_id', $projectTab->id)
        )
            ->resolve();
        $headerObject->project->private_checklists = ChecklistIndexResource::collection(
            $project->checklists->where('user_id', Auth::id())->where('tab_id', $projectTab->id)
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

    public function createFromRequest(Request $request): Checklist
    {
        if ($request->template_id) {
            $template = ChecklistTemplate::where('id', $request->template_id)->first();
            $name = $template->name;
        } else {
            $name = $request->name;
            $template = false;
        }
        $project = $this->projectService->getById($request->project_id);
        $checklist = $this->create(
            name: $name,
            project: $project,
            user: $request->user(),
            tab: ProjectTab::find($request->tab_id),
        );
        if (!$template) {
            $this->setupByTemplate($template, $checklist);
        } else {
            $this->setupByRequest($request, $checklist);
        }

        $this->historyService->createHistory(
            $project->id,
            'Checklist added',
            [$name]
        );

        ProjectHistory::create([
            "user_id" => Auth::id(),
            "project_id" => $request->project_id,
            "description" => "Checkliste $request->name angelegt"
        ]);

        return $checklist;
    }

    public function create(
        string          $name,
        Project         $project,
        User            $user,
        ProjectTab|null $tab = null
    ): Checklist
    {
        $checklist = new Checklist();
        $checklist->name = $name;
        $checklist->project()->associate($project);
        $checklist->user()->associate($user);

        if ($tab) {
            $checklist->projectTab()->associate($tab);
        }

        return $this->checklistRepository->save($checklist);
    }

    protected function setupByTemplate(TaskTemplate $taskTemplate, Checklist $checklist): void
    {
        foreach ($taskTemplate->task_templates as $task_template) {
            Task::create([
                'name' => $task_template->name,
                'description' => $task_template->description,
                'done' => false,
                'checklist_id' => $checklist->id,
                'order' => Task::max('order') + 1,
            ]);
        }

        $checklist->users()->sync(
            collect($template->users)
                ->map(function ($user) {
                    return $user['id'];
                })
        );
    }

    protected function setupByRequest(Request $request, Checklist $checklist): void
    {
        foreach ($request->tasks as $task) {
            Task::create([
                'name' => $task['name'],
                'description' => $task['description'],
                'done' => false,
                'deadline' => $task['deadline_dt_local'],
                'checklist_id' => $checklist->id,
                'order' => Task::max('order') + 1,
            ]);
        }
    }
}
