<?php

namespace Artwork\Modules\Project\Http\Requests;

use Artwork\Modules\Project\Models\ProjectCreateSettings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        $createSettings = app(ProjectCreateSettings::class);
        // Pflicht analog StoreProjectRequest; 'sometimes', damit Aufrufer ohne state-Key
        // (Team-/Schicht-Modals) nicht betroffen sind — die Basisdaten-Modals senden state immer mit.
        $stateRequired = $createSettings->state && $createSettings->state_required;

        return [
            'name' => ['required', 'string', 'max:255'],
            'state' => ['sometimes', $stateRequired ? 'required' : 'nullable', 'integer', 'exists:project_states,id'],
            'description' => ['sometimes', 'nullable', 'string'],
            'number_of_participants' => ['sometimes', 'nullable', 'int'],
            'cost_center' => ['sometimes', 'nullable', 'string'],
            'assignedSectorIds' => ['sometimes', 'array'],
            'assignedCategoryIds' => ['sometimes', 'array'],
            'assignedGenreIds' => ['sometimes', 'array'],
            'assigned_users' => ['sometimes', 'nullable', 'array'],
            'assigned_users.?' => ['exists:users,id'],
            'assigned_departments' => ['sometimes', 'array'],
            'assigned_departments.?' => ['exists:departments,id'],
            'budget_deadline' => 'nullable|date',
            'color' => ['sometimes', 'nullable', 'string'],
            'icon' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
