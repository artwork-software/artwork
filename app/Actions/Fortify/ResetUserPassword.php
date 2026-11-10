<?php

namespace App\Actions\Fortify;

use Artwork\Modules\AppApi\Services\AppTokenService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private readonly AppTokenService $appTokenService)
    {
    }

    public function reset(mixed $user, array $input): void
    {
        // IdP-gebundene Accounts (OIDC/LDAP) haben keinen lokalen Passwort-Login
        // und dürfen kein lokales Passwort setzen.
        if ($user instanceof User && $user->isIdpBound()) {
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

        // Angemeldete App-Geräte müssen sich mit dem neuen Passwort neu anmelden.
        if ($user instanceof User) {
            $this->appTokenService->revokeAll($user);
        }
    }
}
