<?php

namespace Artwork\Modules\Department\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    /**
     * @return string[]
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
