<?php

namespace Artwork\Modules\Project\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectPrintLayoutRequest extends FormRequest
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
            'id' => 'exists:project_print_layouts,id',
            'name' => ['string', 'required', 'max:255'],
            'description' => ['string', 'nullable', 'max:255'],
            'columns_header' => ['integer', 'required', 'min:1', 'max:5'],
            'columns_footer' => ['integer', 'required', 'min:1', 'max:5'],
            'columns_body' => ['integer', 'required', 'min:1', 'max:3'],
            'is_active' => ['boolean', 'required'],
        ];
    }
}
