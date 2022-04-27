<?php

namespace App\Http\Controllers;

use App\Models\ChecklistTemplate;
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
            'checklist_templates' => ChecklistTemplate::paginate(10)->through(fn($checklist_template) => [
                'id' => $checklist_template->id,
                'name' => $checklist_template->name,
                'user' => $checklist_template->user,
            ]),
        ]);
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $checklist_template = ChecklistTemplate::create([
            'name' => $request->name,
            'user_id' => $request->user_id
        ]);

        $checklist_template->task_templates()->createMany($request->task_templates);

        return Redirect::route('departments')->with('success', 'ChecklistTemplate created.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ChecklistTemplate  $checklistTemplate
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(ChecklistTemplate $checklistTemplate)
    {
        return inertia('ChecklistTemplates/Show', [
            'checklist_template' => [
                'id' => $checklistTemplate->id,
                'name' => $checklistTemplate->name,
                'task_templates' => $checklistTemplate->task_templates->map(fn($task_template) => [
                    'id' => $task_template->id,
                    'name' => $task_template->name,
                    'description' => $task_template->description,
                    'done' => $task_template->done,
                ]),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ChecklistTemplate  $checklistTemplate
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit(ChecklistTemplate $checklistTemplate)
    {
        return inertia('ChecklistTemplates/Edit', [
            'checklist_template' => [
                'id' => $checklistTemplate->id,
                'name' => $checklistTemplate->name,
                'user' => $checklistTemplate->user,
                ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ChecklistTemplate  $checklistTemplate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ChecklistTemplate $checklistTemplate)
    {
        $checklistTemplate->update($request->only('name'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ChecklistTemplate  $checklistTemplate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(ChecklistTemplate $checklistTemplate)
    {
        $checklistTemplate->delete();
        return Redirect::back()->with('success', 'ChecklistTemplate deleted');
    }
}
