<?php

namespace App\Actions\Fortify;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function update(mixed $user, array $input): void
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:3072'],
            'phone_number' => ['nullable','string', 'max:15'],
            'position' => ['nullable', 'string', 'max:255'],
            'business' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable','string', 'max:5000']
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        if ($input['email'] !== $user->email && $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => $input['email'],
                'phone_number' => $input['phone_number'] ?? null,
                'position' => $input['position'] ?? null,
                'business' => $input['business'] ?? null,
                'description' => $input['description'] ?? null,
            ])->save();
        }
    }

    protected function updateVerifiedUser(mixed $user, array $input): void
    {
        $user->forceFill([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'email_verified_at' => null,
            'phone_number' => $input['phone_number'],
            'position' => $input['position'],
            'business' => $input['business'],
            'description' => $input['description'],
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
