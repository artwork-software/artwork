<?php

namespace Artwork\Modules\Mail\Http\Requests;

use Artwork\Modules\Role\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class TestMailRequest extends FormRequest
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
            'recipient' => ['required', 'email', 'max:255'],
        ];
    }
}
