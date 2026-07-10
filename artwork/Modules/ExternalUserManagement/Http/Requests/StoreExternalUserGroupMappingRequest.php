<?php

namespace Artwork\Modules\ExternalUserManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExternalUserGroupMappingRequest extends FormRequest
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
            'source_id' => ['required', 'integer', 'exists:external_user_sources,id'],
            'ad_group_dn' => ['required', 'string', 'max:500'],
            'ad_group_name' => ['required', 'string', 'max:255'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'include_nested_groups' => ['sometimes', 'boolean'],
        ];
    }
}

