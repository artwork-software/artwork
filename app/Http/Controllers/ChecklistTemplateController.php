<?php

namespace App\Http\Controllers;

use Artwork\Core\Http\Requests\SearchRequest;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\ChecklistTemplate\Http\Resources\ChecklistTemplateIndexResource;
use Artwork\Modules\ChecklistTemplate\Models\ChecklistTemplate;
use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class ChecklistTemplateController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ChecklistTemplate::class);
    }

    public function index(): Response|ResponseFactory
    {
        return inertia('ChecklistTemplates/ChecklistTemplateManagement', [
            'checklist_templates' => ChecklistTemplateIndexResource::collection(ChecklistTemplate::all())->resolve(),
        ]);
    }

    /**
     * @param SearchRequest $request
     * @return array<string, mixed>
     */
    public function search(SearchRequest $request): array
    {
        return ChecklistTemplateIndexResource::collection(
            ChecklistTemplate::search($request->input('query'))->get()
        )->resolve();
    }

    public function create(): Response|ResponseFactory
    {
        return inertia('ChecklistTemplates/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->checklist_id) {
            $this->createFromChecklist($request);
        } else {
            $this->createFromScratch($request);
        }

        return Redirect::route('checklist_templates.management');
    }

    protected function createFromChecklist(Request $request): void
    {
        $checklist = Checklist::where('id', $request->checklist_id)->first();

        $checklist_template = ChecklistTemplate::create([
            'name' => $checklist->name,
            'user_id' => $request->user_id
        ]);

        foreach ($checklist->tasks as $task) {
            TaskTemplate::create([
                'name' => $task->name,
                'description' => $task->description,
                'done' => false,
                'checklist_template_id' => $checklist_template->id
            ]);
        }

        $checklist_template->users()->sync(collect($checklist->users)->pluck('id'));
    }

    protected function createFromScratch(Request $request): void
    {
        $checklist_template = ChecklistTemplate::create([
            'name' => $request->name,
            'user_id' => $request->user_id
        ]);

        $checklist_template->users()->sync(Collection::make($request->users)->pluck('id'));

        if ($request->task_templates) {
            $checklist_template->task_templates()->createMany($request->task_templates);
        }
    }

    public function show(ChecklistTemplate $checklistTemplate): Response|ResponseFactory
    {
        return inertia('ChecklistTemplates/Show', [
            'checklist_template' => new ChecklistTemplateIndexResource($checklistTemplate)
        ]);
    }

    public function edit(ChecklistTemplate $checklistTemplate): Response|ResponseFactory
    {
        return inertia('ChecklistTemplates/Edit', [
            'checklist_template' => new ChecklistTemplateIndexResource($checklistTemplate)
        ]);
    }

    public function update(Request $request, ChecklistTemplate $checklistTemplate): RedirectResponse
    {
        $checklistTemplate->update($request->only('name'));

        $userIdsToSync = Collection::make($request->users)->pluck('id');
        $checklistTemplate->users()->sync($userIdsToSync);

        if ($request->task_templates) {
            $checklistTemplate->task_templates()->delete();
            foreach ($request->task_templates as $task_template) {
                $task_template_new = $checklistTemplate->task_templates()->create($task_template);
                $task_template_new->task_users()->sync($userIdsToSync);
            }
        }

        return Redirect::route('checklist_templates.management', $checklistTemplate->id);
    }

    public function destroy(ChecklistTemplate $checklistTemplate): RedirectResponse
    {
        $checklistTemplate->delete();

        return Redirect::back();
    }
}
