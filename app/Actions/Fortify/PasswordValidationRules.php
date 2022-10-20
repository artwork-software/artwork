<?php

namespace App\Actions\Fortify;

use ZxcvbnPhp\Zxcvbn;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array
     */
    protected function passwordRules()
    {
        return [
            'required',
            'string',
            fn ($attribute, $value, $fail) => (new Zxcvbn())->passwordStrength($value)['score'] >= 3
                ?: $fail('Bitte wähle ein stärkeres Passwort, in dem du z.B. Buchstaben, Zahlen und Sonderzeichen verwendest.'),
        ];
    }
}
