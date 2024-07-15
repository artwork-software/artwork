<?php

namespace Artwork\Modules\Project\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectCreateSettingsUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attributes' => 'required|boolean',
            'state' => 'required|boolean',
            'managers' => 'required|boolean',
            'cost_center' => 'required|boolean',
            'budget_deadline' => 'required|boolean',
        ];
    }
}
