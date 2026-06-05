<?php

namespace App\Http\Controllers;

use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class TaskTemplateController extends Controller
{
    public function create(): Response|ResponseFactory
    {
        return inertia('TaskTemplates/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $nextOrder = TaskTemplate::where('checklist_template_id', $request->checklist_template_id)
            ->max('order');

        TaskTemplate::create([
            'name' => $request->name,
            'description' => $request->description,
            'done' => false,
            'checklist_template_id' => $request->checklist_template_id,
            'order' => $nextOrder !== null ? $nextOrder + 1 : 0,
            'deadline_days_after_creation' => $request->deadline_days_after_creation
        ]);

        return Redirect::back();
    }

    public function edit(TaskTemplate $taskTemplate): Response|ResponseFactory
    {
        return inertia('TaskTemplates/Edit', [
            'task_templates' => [
                'name' => $taskTemplate->name,
                'description' => $taskTemplate->description,
                'done' => $taskTemplate->done,
            ]
        ]);
    }

    public function update(Request $request, TaskTemplate $taskTemplate): RedirectResponse
    {
        $taskTemplate->update(
            $request->only('name', 'description', 'done', 'checklist_template_id', 'deadline_days_after_creation')
        );

        return Redirect::back();
    }

    public function updateOrder(Request $request): RedirectResponse
    {
        foreach ($request->collect('taskTemplates') as $taskTemplate) {
            TaskTemplate::where('id', $taskTemplate['id'])->update(['order' => $taskTemplate['order']]);
        }

        return Redirect::back();
    }

    public function destroy(TaskTemplate $taskTemplate): RedirectResponse
    {
        $taskTemplate->delete();

        return Redirect::back();
    }
}
