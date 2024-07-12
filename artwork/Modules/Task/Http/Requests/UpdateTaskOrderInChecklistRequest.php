<?php

namespace Artwork\Modules\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskOrderInChecklistRequest extends FormRequest
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
            'checklistTasks' => 'required|array|min:1',
            'checklistTasks.*.id' => 'required|integer|exists:tasks,id',
            'checklistTasks.*.order' => 'required|integer',
        ];
    }
}
