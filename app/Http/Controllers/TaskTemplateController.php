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

    /**
     * Regeln für Anlage/Änderung einer Aufgabenvorlage. Die Vorlagenzugehörigkeit wird nur per exists
     * geprüft — die Routen liegen komplett hinter "can:admin checklistTemplates", ein feineres Recht
     * pro Vorlage gibt es nicht (Sicherheits-Audit 21.09.2026, E).
     *
     * @return array<string, array<int, string>>
     */
    private static function rules(bool $update): array
    {
        $sometimes = $update ? ['sometimes'] : [];

        return [
            'checklist_template_id' => [...$sometimes, 'required', 'integer', 'exists:checklist_templates,id'],
            'name' => [...$sometimes, 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'done' => ['nullable', 'boolean'],
            'deadline_days_after_creation' => ['nullable', 'integer', 'min:0', 'max:36500'],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(self::rules(false));

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
        $request->validate(self::rules(true));

        $taskTemplate->update(
            $request->only('name', 'description', 'done', 'checklist_template_id', 'deadline_days_after_creation')
        );

        return Redirect::back();
    }

    public function updateOrder(Request $request): RedirectResponse
    {
        $request->validate([
            'taskTemplates' => ['required', 'array'],
            'taskTemplates.*.id' => ['required', 'integer', 'exists:task_templates,id'],
            'taskTemplates.*.order' => ['required', 'integer', 'min:0'],
        ]);

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
