<?php

namespace Artwork\Modules\ExternalUserManagement\Service;

use Artwork\Modules\ExternalUserManagement\Exceptions\AdminLockoutException;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;

/**
 * Schützt davor, dass sämtliche Accounts mit Admin-Rolle gleichzeitig
 * IdP-gebunden sind – sonst sperrt ein IdP-Ausfall die Instanz komplett aus.
 */
class AdminLockoutGuard
{
    /**
     * Stellt sicher, dass durch das IdP-Binden von $user nicht der letzte
     * lokal authentifizierte Admin verschwindet.
     *
     * @throws AdminLockoutException
     */
    public function assertNotLastLocalAdmin(User $user): void
    {
        // Nur relevant, wenn der betroffene Account überhaupt Admin ist …
        if (!$user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return;
        }

        // … und aktuell noch lokal authentifiziert (sonst ändert sich nichts).
        if ($user->isIdpBound()) {
            return;
        }

        if ($this->localAdminCount() <= 1) {
            throw new AdminLockoutException(
                'Refusing to bind the last local admin account to an identity provider. '
                . 'Keep at least one admin with local password login as a break-glass account.'
            );
        }
    }

    /**
     * Admin-Konten werden nie per E-Mail an eine LDAP-Identität gebunden – weder erstverknüpft noch
     * auf ein neues Subject umgeschlüsselt: das mail-Attribut ist in vielen Verzeichnissen vom
     * Nutzer selbst schreibbar – wer dort die Adresse eines Admins einträgt, bekäme sonst dessen
     * Konto. Lokale Admins melden sich weiter mit ihrem lokalen Passwort an.
     *
     * @throws AdminLockoutException
     */
    public function assertMayLinkByEmail(User $user, string $provider): void
    {
        if ($provider !== 'ldap' || !$user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return;
        }

        throw new AdminLockoutException(
            'Refusing to link a local admin account to LDAP by email address. '
            . 'Admin accounts keep their local password login.'
        );
    }

    /**
     * Lokale Admins melden sich am Passwortformular immer lokal an, auch wenn ihre Adresse im
     * Verzeichnis steht – sie werden ohnehin nicht per E-Mail an LDAP gebunden.
     */
    public function keepsLocalLogin(User $user): bool
    {
        return !$user->isIdpBound() && $user->hasRole(RoleEnum::ARTWORK_ADMIN->value);
    }

    /**
     * Anzahl der Accounts mit Admin-Rolle, die weiterhin lokal einloggen können.
     */
    public function localAdminCount(): int
    {
        return User::role(RoleEnum::ARTWORK_ADMIN->value)
            ->where('auth_provider', 'local')
            ->count();
    }
}
