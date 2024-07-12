<?php

namespace Artwork\Modules\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:tasks,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'deadlineDate' => 'required',
            'checklist_id' => 'required|integer|exists:checklists,id',
            'users' => 'array|nullable',
            'users.*.id' => 'integer|exists:users,id',

        ];
    }
}
