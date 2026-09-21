<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Ohne uncompromised(): Intranet-Installationen laufen ohne Internetzugang.
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
