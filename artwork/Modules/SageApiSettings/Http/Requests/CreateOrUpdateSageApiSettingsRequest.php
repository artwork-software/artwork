<?php

namespace Artwork\Modules\SageApiSettings\Http\Requests;

use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrUpdateSageApiSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Nur https: Basic-Auth-Zugangsdaten gehen sonst im Klartext über die Leitung.
            'host' => ['required', 'string', 'max:2048', 'url:https'],
            'endpoint' => ['required', 'string', 'max:2048'],
            'user' => ['required', 'string', 'max:255'],
            // Leer = gespeichertes Passwort behalten; beim ersten Anlegen und bei einem neuen Host Pflicht,
            // sonst ginge das gespeicherte Passwort beim Verbindungstest an einen beliebigen Server.
            'password' => [
                Rule::requiredIf(fn (): bool => $this->passwordMustBeEntered()),
                'nullable',
                'string',
                'max:1024',
            ],
            'bookingDate' => 'date|nullable',
            'fetchTime' => 'string|nullable',
            'enabled' => 'boolean',
            'verify_ssl' => 'boolean|nullable',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => __('Enter the password again when changing the Sage host.'),
            'host.url' => __('The Sage host must be an https address.'),
        ];
    }

    private function passwordMustBeEntered(): bool
    {
        $stored = SageApiSettings::query()->first();
        if ($stored === null) {
            return true;
        }

        $normalize = static fn (mixed $host): string => rtrim(strtolower(trim((string) $host)), '/');

        return $normalize($stored->host) !== $normalize($this->input('host'));
    }
}
