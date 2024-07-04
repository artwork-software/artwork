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
            'state' => 'required|integer|exists:project_states,id',
            'cost_center' => 'required|string|max:255',
            'projects.*' => 'exists:projects,id',
        ];
    }
}
