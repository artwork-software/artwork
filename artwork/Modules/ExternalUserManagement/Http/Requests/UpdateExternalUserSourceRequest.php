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
        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
            'type' => ['sometimes', 'required', 'string', 'in:ldap,identity_provider'],
            'config' => ['sometimes', 'required', 'array'],
        ];

        $source = $this->route('externalUserSource');
        $type = $this->input('type') ?? $source?->type;

        if ($type === 'identity_provider') {
            return $rules + [
                'config.discovery_url' => ['sometimes', 'required', 'url', 'max:500'],
                'config.client_id' => ['sometimes', 'required', 'string', 'max:255'],
                'config.client_secret' => ['sometimes', 'required', 'string', 'max:1000'],
                'config.scopes' => ['sometimes', 'array'],
                'config.scopes.*' => ['string', 'max:100'],
                'config.identifier_attribute' => ['nullable', 'string', 'max:100'],
                'config.groups_claim' => ['nullable', 'string', 'max:100'],
                'config.allowed_domains' => ['sometimes', 'required', 'array', 'min:1'],
                'config.allowed_domains.*' => ['string', 'max:255'],
            ];
        }

        return $rules + [
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

