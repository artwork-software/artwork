<?php

namespace Artwork\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryTagRequest extends FormRequest
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
            'id'                     => ['required', 'integer', 'exists:inventory_tags,id'],
            'name'                   => ['required', 'string', 'max:255'],
            'color'                  => ['required', 'string', 'max:7'],
            'has_restricted_permissions' => ['boolean'],
            'permission_mode'        => ['nullable', 'in:all_can_edit,restricted_edit'],
            'inventory_tag_group_id' => ['nullable', 'integer', 'exists:inventory_tag_groups,id'],

            'user_ids'        => ['array'],
            'user_ids.*'      => ['integer', 'exists:users,id'],
            'department_ids'  => ['array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
        ];
    }
}
