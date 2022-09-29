<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'number_of_participants' => ['sometimes', 'nullable', 'int'],
            'cost_center' => ['sometimes', 'nullable', 'string'],
            'sector_id' => ['sometimes', 'nullable', 'exists:sectors,id'],
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'genre_id' => ['sometimes', 'nullable', 'exists:genres,id'],
            'assigned_users' => ['sometimes', 'nullable', 'array'],
            'assigned_users.?' => ['exists:users,id'],
            'assigned_departments' => ['sometimes', 'array'],
            'assigned_departments.?' => ['exists:departments,id'],
        ];
    }
}
