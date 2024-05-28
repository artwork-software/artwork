<?php

namespace App\Http\Controllers;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Checklist\Http\Requests\ChecklistUpdateRequest;
use Artwork\Modules\Checklist\Http\Resources\ChecklistShowResource;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\ChecklistTemplate\Models\ChecklistTemplate;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Task\Services\TaskService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class ChecklistController extends Controller
{
    public function __construct(
        private readonly ChecklistService $checklistService,
        private readonly ChangeService $changeService
    ) {
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

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Project::class)
                ->setModelId($request->project_id)
                ->setTranslationKey('Checklist added')
                ->setTranslationKeyPlaceholderValues([$request->name])
        );

        return Redirect::back();
    }

    protected function createFromTemplate(Request $request): void
    {
        $template = ChecklistTemplate::where('id', $request->template_id)->first();

        $checklist = Checklist::create([
            'name' => $template->name,
            'project_id' => $request->project_id,
            'user_id' => $request->user_id,
            'tab_id' => $request->tab_id ?? null
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
            'user_id' => $request->user_id,
            'tab_id' => $request->tab_id ?? null
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

    public function update(
        ChecklistUpdateRequest $request,
        Checklist $checklist,
        TaskService $taskService
    ): RedirectResponse {
        $this->checklistService->updateByRequest($checklist, $request, $taskService);

        if ($request->missing('assigned_user_ids')) {
            return Redirect::back();
        }

        $this->checklistService->assignUsersById($checklist, $taskService, $request->assigned_user_ids ?? []);

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Project::class)
                ->setModelId($checklist->project_id)
                ->setTranslationKey('Checklist modified')
                ->setTranslationKeyPlaceholderValues([$checklist->name])
        );

        return Redirect::back();
    }

    public function destroy(
        Checklist $checklist
    ): RedirectResponse {
        $checklist->forceDelete();

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Project::class)
                ->setModelId($checklist->project_id)
                ->setTranslationKey('Checklist removed')
                ->setTranslationKeyPlaceholderValues([$checklist->name])
        );

        return Redirect::back();
    }
}
