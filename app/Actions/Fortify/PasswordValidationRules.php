<?php

namespace App\Actions\Fortify;

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
            'min:10'
        ];
    }
}
