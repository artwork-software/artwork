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
     * Anzahl der Accounts mit Admin-Rolle, die weiterhin lokal einloggen können.
     */
    public function localAdminCount(): int
    {
        return User::role(RoleEnum::ARTWORK_ADMIN->value)
            ->where('auth_provider', 'local')
            ->count();
    }
}
