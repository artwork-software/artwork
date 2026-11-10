<?php

namespace Artwork\Modules\User\Services;

use Artwork\Core\Validation\Rules\NotReservedUserEmail;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Notifications\ConfirmEmailChangeNotification;
use Artwork\Modules\User\Notifications\EmailAddressChangedNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Regeln für den Wechsel der Login-E-Mail. Die Adresse ist Login, Reset-Ziel und Schlüssel für die
 * Verknüpfung mit LDAP/OIDC-Identitäten; ein ungeprüfter Wechsel öffnet Kontoübernahmen. Die eigene
 * Adresse wechselt deshalb erst, wenn der Link an die neue Adresse bestätigt wurde.
 */
class UserEmailChangeService
{
    public const CONFIRMATION_MINUTES = 60;

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

    /**
     * Merkt die neue Adresse vor und schickt den Bestätigungslink dorthin. Ein erneuter Antrag ersetzt
     * den vorigen; dessen Link passt dann nicht mehr.
     */
    public function requestSelfChange(User $user, string $newEmail): void
    {
        $newEmail = trim($newEmail);
        $user->forceFill(['pending_email' => $newEmail])->save();

        $confirmationUrl = URL::temporarySignedRoute(
            'user.email-change.confirm',
            now()->addMinutes(self::CONFIRMATION_MINUTES),
            ['user' => $user->id, 'hash' => $this->pendingEmailHash($newEmail)]
        );

        Notification::route('mail', $newEmail)->notify(
            (new ConfirmEmailChangeNotification($user->full_name, $confirmationUrl, self::CONFIRMATION_MINUTES))
                ->locale($user->preferredLocale() ?? app()->getLocale())
        );
    }

    /**
     * Übernimmt die vorgemerkte Adresse, wenn der Link zu ihr gehört. Eindeutigkeit, reservierte
     * Domains und IdP-Bindung werden erneut geprüft – seit dem Antrag kann sich beides geändert haben.
     *
     * @throws ValidationException
     */
    public function confirmSelfChange(User $user, string $hash): void
    {
        $pendingEmail = $user->pending_email;
        if ($pendingEmail === null || !hash_equals($this->pendingEmailHash($pendingEmail), $hash)) {
            throw ValidationException::withMessages([
                'email' => __('This confirmation link is no longer valid.'),
            ]);
        }

        if ($user->isIdpBound()) {
            $user->forceFill(['pending_email' => null])->save();
            throw ValidationException::withMessages([
                'email' => __(
                    'Your email address is managed by your organisation\'s sign-in and cannot be changed here.'
                ),
            ]);
        }

        $validator = Validator::make(['email' => $pendingEmail], ['email' => $this->emailRules($user)]);
        if ($validator->fails()) {
            $user->forceFill(['pending_email' => null])->save();
            throw new ValidationException($validator);
        }

        $previousEmail = (string) $user->email;
        $user->forceFill(['email' => $pendingEmail, 'pending_email' => null])->save();
        $this->notifyPreviousAddress($user, $previousEmail);
    }

    private function pendingEmailHash(string $email): string
    {
        return sha1(mb_strtolower($email));
    }
}
