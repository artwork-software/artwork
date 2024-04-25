<?php

namespace Artwork\Modules\Project\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
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
        ];
    }
}
