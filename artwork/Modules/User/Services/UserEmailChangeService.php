<?php

namespace Artwork\Modules\User\Services;

use Artwork\Core\Validation\Rules\NotReservedUserEmail;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Notifications\EmailAddressChangedNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Regeln für den Wechsel der Login-E-Mail. Die Adresse ist Login, Reset-Ziel und Schlüssel für die
 * Verknüpfung mit LDAP/OIDC-Identitäten; ein ungeprüfter Wechsel öffnet Kontoübernahmen.
 */
class UserEmailChangeService
{
    /**
     * @return array<int, mixed>
     */
    public function emailRules(User $user): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore($user->id),
            new NotReservedUserEmail(),
        ];
    }

    public function isChange(User $user, mixed $newEmail): bool
    {
        return is_string($newEmail) && strcasecmp(trim($newEmail), (string) $user->email) !== 0;
    }

    /**
     * Die eigene Adresse ändert nur, wer das aktuelle Passwort kennt. IdP-Konten haben kein lokales
     * Passwort; ihre Adresse kommt aus dem Verzeichnis.
     *
     * @throws ValidationException
     */
    public function assertSelfChangeAllowed(
        User $user,
        mixed $currentPassword,
        string $errorBag = 'default'
    ): void {
        if ($user->isIdpBound()) {
            throw ValidationException::withMessages([
                'email' => __(
                    'Your email address is managed by your organisation\'s sign-in and cannot be changed here.'
                ),
            ])->errorBag($errorBag);
        }

        if (
            !is_string($currentPassword)
            || $currentPassword === ''
            || !Hash::check($currentPassword, $user->password)
        ) {
            throw ValidationException::withMessages([
                'current_password' => __('The provided password does not match your current password.'),
            ])->errorBag($errorBag);
        }
    }

    public function notifyPreviousAddress(User $user, string $previousEmail): void
    {
        Notification::route('mail', $previousEmail)->notify(
            (new EmailAddressChangedNotification($user->full_name))
                ->locale($user->preferredLocale() ?? app()->getLocale())
        );
    }
}
