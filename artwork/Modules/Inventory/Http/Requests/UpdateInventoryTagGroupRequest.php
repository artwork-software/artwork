<?php

namespace Artwork\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryTagGroupRequest extends FormRequest
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
            'id'       => ['required', 'integer', 'exists:inventory_tag_groups,id'],
            'name'     => ['required', 'string', 'max:255'],
            'position' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
