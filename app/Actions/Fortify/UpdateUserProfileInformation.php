<?php

namespace App\Actions\Fortify;

use Artwork\Core\FileHandling\Upload\SafeUploadFile;
use Artwork\Modules\User\Services\UserEmailChangeService;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function __construct(private readonly UserEmailChangeService $emailChangeService)
    {
    }

    public function update(mixed $user, array $input): void
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => $this->emailChangeService->emailRules($user),
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:3072', new SafeUploadFile()],
            'phone_number' => ['nullable','string', 'max:15'],
            'position' => ['nullable', 'string', 'max:255'],
            'business' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable','string', 'max:5000']
        ])->validateWithBag('updateProfileInformation');

        $previousEmail = (string) $user->email;
        $emailChanged = $this->emailChangeService->isChange($user, $input['email']);
        if ($emailChanged) {
            $this->emailChangeService->assertSelfChangeAllowed(
                $user,
                $input['current_password'] ?? null,
                'updateProfileInformation'
            );
        }

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        // Keine E-Mail-Verifizierung (Konten nur per Einladung, LDAP oder OIDC): die neue Adresse gilt sofort,
        // verlangt aber das aktuelle Passwort; die bisherige Adresse bekommt einen Hinweis.
        $user->forceFill([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'phone_number' => $input['phone_number'] ?? null,
            'position' => $input['position'] ?? null,
            'business' => $input['business'] ?? null,
            'description' => $input['description'] ?? null,
        ])->save();

        if ($emailChanged) {
            $this->emailChangeService->notifyPreviousAddress($user, $previousEmail);
        }
    }
}
