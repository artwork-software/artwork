<?php

namespace Artwork\Modules\ExternalUserManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExternalUserGroupMappingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('change tool settings');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ad_group_dn' => ['sometimes', 'required', 'string', 'max:500'],
            'ad_group_name' => ['sometimes', 'required', 'string', 'max:255'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'include_nested_groups' => ['sometimes', 'boolean'],
        ];
    }
}

