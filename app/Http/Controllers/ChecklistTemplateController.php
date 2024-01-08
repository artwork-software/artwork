<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Resources\ChecklistTemplateIndexResource;
use Artwork\Modules\Checklist\Models\Checklist;
use App\Models\ChecklistTemplate;
use App\Models\TaskTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ChecklistTemplateController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(ChecklistTemplate::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        return inertia('ChecklistTemplates/ChecklistTemplateManagement', [
            'checklist_templates' => ChecklistTemplateIndexResource::collection(ChecklistTemplate::all())->resolve(),
        ]);
    }

    public function search(SearchRequest $request)
    {
        return ChecklistTemplateIndexResource::collection(ChecklistTemplate::search($request->input('query'))->get())->resolve();

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        return inertia('ChecklistTemplates/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if ($request->checklist_id) {
            $this->createFromChecklist($request);
        } else {
            $this->createFromScratch($request);
        }

        return Redirect::route('checklist_templates.management')->with('success', 'ChecklistTemplate created.');
    }

    protected function createFromChecklist(Request $request)
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

    protected function createFromScratch(Request $request)
    {
        $checklist_template = ChecklistTemplate::create([
            'name' => $request->name,
            'user_id' => $request->user_id
        ]);

        $checklist_template->users()->sync(
            $request->users
        );

        if ($request->task_templates) {
            $checklist_template->task_templates()->createMany($request->task_templates);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\ChecklistTemplate $checklistTemplate
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(ChecklistTemplate $checklistTemplate)
    {
        return inertia('ChecklistTemplates/Show', [
            'checklist_template' => new ChecklistTemplateIndexResource($checklistTemplate)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\ChecklistTemplate $checklistTemplate
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit(ChecklistTemplate $checklistTemplate)
    {
        return inertia('ChecklistTemplates/Edit', [
            'checklist_template' => new ChecklistTemplateIndexResource($checklistTemplate)
        ]);
    }

    public function update(Request $request, ChecklistTemplate $checklistTemplate): \Illuminate\Http\RedirectResponse
    {

        $checklistTemplate->update($request->only('name'));

        $checklistTemplate->users()->sync(
            $request->users
        );

        if ($request->task_templates) {
            $checklistTemplate->task_templates()->delete();
            foreach ($request->task_templates as $task_template) {
                $task_template_new = $checklistTemplate->task_templates()->create($task_template);
                $task_template_new->task_users()->sync($request->users);
            }
        }

        return Redirect::route('checklist_templates.management', $checklistTemplate->id)->with('success', 'ChecklistTemplate updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\ChecklistTemplate $checklistTemplate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(ChecklistTemplate $checklistTemplate)
    {
        $checklistTemplate->delete();

        return Redirect::back()->with('success', 'ChecklistTemplate deleted');
    }
}
