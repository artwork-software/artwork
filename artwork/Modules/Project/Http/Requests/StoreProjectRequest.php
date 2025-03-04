<?php

namespace Artwork\Modules\Project\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'assigned_users.*' => 'exists:users,id',
            'isGroup' => 'required|boolean',
            'budget_deadline' => 'nullable|date',
            'state' => 'nullable|integer|exists:project_states,id',
            'cost_center' => 'nullable|string|max:255',
            'projects.*' => 'exists:projects,id',
            'color' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
        ];
    }
}
