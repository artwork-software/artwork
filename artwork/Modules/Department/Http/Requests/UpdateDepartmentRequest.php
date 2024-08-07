<?php

namespace Artwork\Modules\Department\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'svg_name' => 'required|string|max:255',
            'assigned_users' => 'array'
        ];
    }
}
