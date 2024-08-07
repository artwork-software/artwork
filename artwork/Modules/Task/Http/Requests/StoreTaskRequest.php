<?php

namespace Artwork\Modules\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'deadline' => 'sometimes',
            'checklist_id' => 'required|integer|exists:checklists,id',
            'users' => 'array|nullable',
            'users.*.id' => 'integer|exists:users,id',
        ];
    }
}
