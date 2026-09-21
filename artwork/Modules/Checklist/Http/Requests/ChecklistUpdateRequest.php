<?php

namespace Artwork\Modules\Checklist\Http\Requests;

use Artwork\Modules\Event\Http\Requests\EventStoreOrUpdateRequest;

/**
 * user_id ist nicht Teil des Requests; Projekt-/Tab-Wechsel autorisiert der Controller.
 * Kein data()-Override: filled()/boolean()/integer() lesen über data().
 */
class ChecklistUpdateRequest extends EventStoreOrUpdateRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'private' => ['sometimes', 'boolean'],
            'project_id' => ['sometimes', 'nullable', 'integer', 'exists:projects,id'],
            'tab_id' => ['sometimes', 'nullable', 'integer', 'exists:project_tabs,id'],
            'tasks' => ['sometimes', 'array'],
            'tasks.*.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tasks.*.description' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'tasks.*.done' => ['required', 'nullable', 'boolean'],
            'tasks.*.order' => ['required', 'nullable', 'int'],
            'tasks.*.deadline' => ['sometimes', 'nullable', 'date'],

            'assigned_department_ids' => [
                'sometimes',
                'array',
            ],
            'assigned_department_ids.*.*' => ['required', 'exists:departments,id'],
            'assigned_user_ids' => ['sometimes', 'array'],
            'assigned_user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    /**
     * user_id bleibt beim Ersteller; project_id/tab_id setzt nur der Controller nach Autorisierung.
     *
     * @return array<string, mixed>
     */
    public function fillableFields(): array
    {
        return $this->only(['name', 'private']);
    }
}
