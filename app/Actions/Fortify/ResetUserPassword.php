<?php

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function reset(mixed $user, array $input): void
    {
        // IdP-gebundene Accounts (OIDC/LDAP) haben keinen lokalen Passwort-Login
        // und dürfen kein lokales Passwort setzen.
        if ($user instanceof \Artwork\Modules\User\Models\User && $user->isIdpBound()) {
            throw ValidationException::withMessages([
                'email' => __('flash-messages.oidc.error.password_login_disabled'),
            ]);
        }

        Validator::make(
            $input,
            [
                'password' => $this->passwordRules(),
            ]
        )->validate();

        $user->forceFill(['password' => Hash::make($input['password'])])->save();
    }
}
