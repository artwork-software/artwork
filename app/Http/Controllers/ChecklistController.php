<?php

namespace App\Http\Controllers;

use App\Models\ChecklistTemplate;
use App\Models\Task;
use Artwork\Modules\Checklist\Http\Requests\ChecklistUpdateRequest;
use App\Http\Resources\ChecklistShowResource;
use Artwork\Modules\Checklist\Models\Checklist;
use App\Support\Services\HistoryService;
use App\Support\Services\NewHistoryService;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectHistory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class ChecklistController extends Controller
{
    protected ?NewHistoryService $history = null;

    public function __construct(protected readonly ChecklistService $checklistService)
    {
        $this->authorizeResource(Checklist::class);
    }

    public function create(): ResponseFactory
    {
        return inertia('Checklists/Create');
    }

    /**
     * @throws AuthorizationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('createProperties', Project::find($request->project_id));

        //Check whether checklist should be created on basis of a template
        if ($request->template_id) {
            $this->createFromTemplate($request);
        } else {
            $this->createWithoutTemplate($request);
        }

        $this->history = new NewHistoryService('Artwork\Modules\Project\Models\Project');
        $this->history->createHistory(
            $request->project_id,
            'Checklist added',
            [$request->name]
        );

        ProjectHistory::create([
            "user_id" => Auth::id(),
            "project_id" => $request->project_id,
            "description" => "Checkliste $request->name angelegt"
        ]);

        return Redirect::back();
    }

    protected function createFromTemplate(Request $request): void
    {
        $template = ChecklistTemplate::where('id', $request->template_id)->first();

        $checklist = Checklist::create([
            'name' => $template->name,
            'project_id' => $request->project_id,
            'user_id' => $request->user_id
        ]);

        foreach ($template->task_templates as $task_template) {
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

    protected function createWithoutTemplate(Request $request): void
    {
        $checklist = Checklist::create([
            'name' => $request->name,
            'project_id' => $request->project_id,
            'user_id' => $request->user_id
        ]);

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

    public function show(Checklist $checklist): Response|ResponseFactory
    {
        return inertia('Checklists/Show', [
            'checklist' => new ChecklistShowResource($checklist),
        ]);
    }

    public function edit(Checklist $checklist): Response|ResponseFactory
    {
        return inertia('Checklists/Edit', [
            'checklist' => new ChecklistShowResource($checklist),
        ]);
    }

    public function update(ChecklistUpdateRequest $request, Checklist $checklist): RedirectResponse
    {
        $this->checklistService->updateByRequest($checklist, $request);

        if ($request->missing('assigned_user_ids')) {
            return Redirect::back();
        }

        $this->checklistService->assignUsersById($checklist, $request->assigned_user_ids);

        $this->history = new NewHistoryService(Project::class);
        $this->history->createHistory(
            $checklist->project_id,
            'Checklist modified',
            [$checklist->name]
        );

        return Redirect::back();
    }

    public function destroy(Checklist $checklist, HistoryService $historyService): RedirectResponse
    {
        $this->history = new NewHistoryService(Project::class);
        $this->history->createHistory(
            $checklist->project_id,
            'Checklist removed',
            [$checklist->name]
        );
        $checklist->delete();
        $historyService->checklistUpdated($checklist);

        return Redirect::back();
    }
}
