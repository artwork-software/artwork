<?php

namespace Artwork\Modules\ExternalUserManagement\Http\Requests;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

        if ($this->resolvedType() === 'identity_provider') {
            return $rules + $this->identityProviderRules($this->resolvedPreset());
        }

        return $rules + $this->ldapRules();
    }

    private function resolvedType(): ?string
    {
        /** @var ExternalUserSource|null $source */
        $source = $this->route('externalUserSource');

        return $this->input('type') ?? $source?->type;
    }

    private function resolvedPreset(): string
    {
        /** @var ExternalUserSource|null $source */
        $source = $this->route('externalUserSource');

        return $this->input('config.provider_preset')
            ?? $source?->config['provider_preset']
            ?? 'custom';
    }

    /**
     * @return array<string, mixed>
     */
    protected function identityProviderRules(string $preset): array
    {
        $rules = [
            'config.provider_preset' => ['sometimes', 'string', 'in:google,microsoft,custom'],
            'config.client_id' => ['sometimes', 'required', 'string', 'max:255'],
            'config.client_secret' => ['sometimes', 'required', 'string', 'max:1000'],
            'config.scopes' => ['sometimes', 'array'],
            'config.scopes.*' => ['string', 'max:100'],
            'config.identifier_attribute' => ['nullable', 'string', 'max:100'],
            'config.groups_claim' => ['nullable', 'string', 'max:100'],
            'config.allowed_domains' => ['sometimes', 'nullable', 'array'],
            'config.allowed_domains.*' => ['string', 'max:255'],
            'config.default_role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ];

        if ($preset === 'microsoft') {
            $rules['config.tenant_id'] = ['sometimes', 'required', 'string', 'max:255'];
        } elseif ($preset !== 'google') {
            $rules['config.discovery_url'] = ['sometimes', 'required', 'url', 'max:500'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var ExternalUserSource|null $source */
            $source = $this->route('externalUserSource');
            $active = $this->has('active') ? $this->boolean('active') : (bool) $source?->active;
            $config = $this->has('config') ? $this->input('config', []) : ($source?->config ?? []);

            if ($this->resolvedType() === 'identity_provider'
                && $active
                && empty($config['allowed_domains'] ?? [])) {
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
            'config.host' => ['sometimes', 'required', 'string', 'max:255'],
            'config.port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'config.base_dn' => ['sometimes', 'required', 'string', 'max:500'],
            'config.bind_dn' => ['sometimes', 'required', 'string', 'max:500'],
            'config.bind_password' => ['sometimes', 'required', 'string'],
            'config.use_ssl' => ['sometimes', 'boolean'],
            'config.use_tls' => ['sometimes', 'boolean'],
            'config.user_filter' => ['nullable', 'string', 'max:1000'],
            'config.identifier_attribute' => ['nullable', 'string', 'max:100'],
            'config.default_role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ];
    }
}
