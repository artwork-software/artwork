<?php

namespace Artwork\Modules\Mail\Http\Requests;

use Artwork\Modules\Role\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMailSettingsRequest extends FormRequest
{
    /**
     * Nur artwork-Admins: Wer den SMTP-Server bestimmt, liest Passwort-Reset-, Einladungs- und
     * Magic-Links mit und übernimmt damit jedes Konto („Tool-Einstellungen ändern“ reicht nicht).
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(RoleEnum::ARTWORK_ADMIN->value);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['nullable', 'in:tls,ssl'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:1000'],
            'clear_password' => ['sometimes', 'boolean'],
            'from_address' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
