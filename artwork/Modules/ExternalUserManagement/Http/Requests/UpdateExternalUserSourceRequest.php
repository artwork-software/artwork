<?php

namespace Artwork\Modules\ExternalUserManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExternalUserSourceRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
            'type' => ['sometimes', 'required', 'string', 'in:ldap,identity_provider'],
            'config' => ['sometimes', 'required', 'array'],
            'config.host' => ['sometimes', 'required', 'string', 'max:255'],
            'config.port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'config.base_dn' => ['sometimes', 'required', 'string', 'max:500'],
            'config.bind_dn' => ['sometimes', 'required', 'string', 'max:500'],
            'config.bind_password' => ['sometimes', 'required', 'string'],
            'config.use_ssl' => ['sometimes', 'boolean'],
            'config.use_tls' => ['sometimes', 'boolean'],
            'config.user_filter' => ['nullable', 'string', 'max:1000'],
            'config.identifier_attribute' => ['nullable', 'string', 'max:100'],
        ];
    }
}

