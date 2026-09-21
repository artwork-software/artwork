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
            // Basic-Auth geht nur an eine echte http(s)-Adresse; ohne Schema/Validierung ließe sich
            // der Client auf beliebige Ziele umbiegen.
            'host' => ['required', 'string', 'max:2048', 'url:http,https'],
            'endpoint' => ['required', 'string', 'max:2048'],
            'user' => ['required', 'string', 'max:255'],
            // Leer = gespeichertes Passwort behalten; beim ersten Anlegen ist es Pflicht.
            'password' => [
                Rule::requiredIf(static fn (): bool => SageApiSettings::query()->doesntExist()),
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
}
