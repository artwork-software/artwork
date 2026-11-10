<?php

namespace App\Actions\Fortify;

use Artwork\Modules\AppApi\Services\AppTokenService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private readonly AppTokenService $appTokenService)
    {
    }

    /**
     * @throws ValidationException
     */
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

        // Angemeldete App-Geräte müssen sich mit dem neuen Passwort neu anmelden.
        if ($user instanceof User) {
            $this->appTokenService->revokeAll($user);
        }
    }
}
