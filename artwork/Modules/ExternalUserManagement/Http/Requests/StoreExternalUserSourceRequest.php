<?php

namespace Artwork\Modules\ExternalUserManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExternalUserSourceRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
            'type' => ['required', 'string', 'in:ldap,identity_provider'],
            'config' => ['required', 'array'],
        ];

        if ($this->input('type') === 'identity_provider') {
            return $rules + $this->identityProviderRules($this->input('config.provider_preset', 'custom'));
        }

        return $rules + $this->ldapRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function identityProviderRules(string $preset): array
    {
        $rules = [
            'config.provider_preset' => ['sometimes', 'string', 'in:google,microsoft,custom'],
            'config.client_id' => ['required', 'string', 'max:255'],
            'config.client_secret' => ['required', 'string', 'max:1000'],
            'config.scopes' => ['sometimes', 'array'],
            'config.scopes.*' => ['string', 'max:100'],
            'config.identifier_attribute' => ['nullable', 'string', 'max:100'],
            'config.groups_claim' => ['nullable', 'string', 'max:100'],
            'config.allowed_domains' => ['nullable', 'array'],
            'config.allowed_domains.*' => ['string', 'max:255'],
            'config.default_role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ];

        // Discovery-URL wird für Google/Microsoft aus dem Preset abgeleitet und
        // muss daher nur bei Custom eingegeben werden.
        if ($preset === 'microsoft') {
            $rules['config.tenant_id'] = ['required', 'string', 'max:255'];
        } elseif ($preset !== 'google') {
            $rules['config.discovery_url'] = ['required', 'url', 'max:500'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $isActiveIdentityProvider = $this->input('type') === 'identity_provider'
                && $this->boolean('active', true);

            if ($isActiveIdentityProvider && empty($this->input('config.allowed_domains', []))) {
                $validator->errors()->add(
                    'config.allowed_domains',
                    __('An active identity provider requires at least one allowed email domain.')
                );
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function ldapRules(): array
    {
        return [
            'config.host' => ['required', 'string', 'max:255'],
            'config.port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'config.base_dn' => ['required', 'string', 'max:500'],
            'config.bind_dn' => ['required', 'string', 'max:500'],
            'config.bind_password' => ['required', 'string'],
            'config.use_ssl' => ['sometimes', 'boolean'],
            'config.use_tls' => ['sometimes', 'boolean'],
            'config.user_filter' => ['nullable', 'string', 'max:1000'],
            'config.identifier_attribute' => ['nullable', 'string', 'max:100'],
            'config.default_role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ];
    }
}
