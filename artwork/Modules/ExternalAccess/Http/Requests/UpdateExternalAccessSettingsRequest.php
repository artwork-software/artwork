<?php

namespace Artwork\Modules\ExternalAccess\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateExternalAccessSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // route is gated by role:artwork admin middleware
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['company_name_override' => (string) $this->input('company_name_override', '')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name_override' => ['nullable', 'string', 'max:255'],
            'default_crm_access_months' => ['required', 'integer', 'min:1', 'max:120'],
            'default_tab_access_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'login_token_lifetime_minutes' => ['required', 'integer', 'min:5', 'max:60'],
            'session_idle_timeout_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'session_absolute_lifetime_minutes' => ['required', 'integer', 'min:60', 'max:1440'],
            'rate_limit_request_link_per_email_per_hour' => ['required', 'integer', 'min:1', 'max:100'],
            'rate_limit_request_link_per_ip_per_hour' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $idle = (int) $this->input('session_idle_timeout_minutes');
            $absolute = (int) $this->input('session_absolute_lifetime_minutes');
            if ($idle > $absolute) {
                $v->errors()->add(
                    'session_idle_timeout_minutes',
                    __('Idle timeout cannot exceed absolute session lifetime.'),
                );
            }
        });
    }
}
