<?php

namespace App\Actions\Fortify;

use ZxcvbnPhp\Zxcvbn;

trait PasswordValidationRules
{
    /**
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
            fn ($attribute, $value, $fail) => (new Zxcvbn())->passwordStrength($value)['score'] >= 3 ?: $fail(
                'Bitte wähle ein stärkeres Passwort, in dem du z.B. Buchstaben, Zahlen und Sonderzeichen verwendest.'
            )
        ];
    }
}
