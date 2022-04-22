<?php

namespace App\Http\Controllers;

use App\Models\ChecklistTemplate;
use App\Models\TaskTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class TaskTemplateController extends Controller
{

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        return inertia('TaskTemplates/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if(Auth::user()->can('update checklist_templates')) {

            TaskTemplate::create([
                'name' => $request->name,
                'description' => $request->description,
                'done' => false,
                'checklist_template_id' => $request->checklist_template_id
            ]);
        }
        return Redirect::back()->with('success', 'TaskTemplate created.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TaskTemplate  $taskTemplate
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit(TaskTemplate $taskTemplate)
    {
        return inertia('TaskTemplates/Edit', [
            'task_templates' => [
                'name' => $taskTemplate->name,
                'description' => $taskTemplate->description,
                'deadline' => $taskTemplate->deadline,
                'done' => $taskTemplate->done,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TaskTemplate  $taskTemplate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, TaskTemplate $taskTemplate)
    {
        $taskTemplate->update($request->only('name', 'description','done', 'checklist_template_id'));

        return Redirect::back()->with('success', 'TaskTemplate updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TaskTemplate  $taskTemplate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(TaskTemplate $taskTemplate)
    {
        $taskTemplate->delete();
        return Redirect::back()->with('success', 'TaskTemplate deleted');
    }
}
