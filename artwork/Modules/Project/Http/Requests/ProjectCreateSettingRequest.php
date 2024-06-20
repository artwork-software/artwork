<?php

namespace Artwork\Modules\Project\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectCreateSettingRequest extends FormRequest
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
            'attributes' => 'required|boolean',
            'state' => 'required|boolean',
            'managers' => 'required|boolean',
            'cost_center' => 'required|boolean',
            'budget_deadline' => 'required|boolean',
        ];
    }
}
