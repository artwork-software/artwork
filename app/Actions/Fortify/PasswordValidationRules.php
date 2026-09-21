<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Zentrale Passwortregel (Setup, Einladung, Passwort ändern/zurücksetzen).
     * Bewusst ohne uncompromised(): Intranet-Installationen laufen ohne Internetzugang.
     *
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            Password::min(10)->letters()->mixedCase()->numbers(),
        ];
    }
}
