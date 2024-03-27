<?php

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    public function update(mixed $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string'],
            'password' => $this->passwordRules(),
        ])->after(
            function ($validator) use ($user, $input): void {
                if (! isset($input['current_password']) || ! Hash::check($input['current_password'], $user->password)) {
                    $validator->errors()->add(
                        'current_password',
                        'Das eingegebene Passwort entspricht nicht Ihrem aktuellen Passwort.'
                    );
                }
            }
        )->validateWithBag('updatePassword');

        $user->forceFill(['password' => Hash::make($input['password'])])->save();
    }
}
