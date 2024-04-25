<?php

namespace Artwork\Modules\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'deadline' => 'sometimes',
            'checklist_id' => 'required|integer|exists:checklists,id',
        ];
    }
}
