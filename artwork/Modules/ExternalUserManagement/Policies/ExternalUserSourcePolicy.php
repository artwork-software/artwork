<?php

namespace Artwork\Modules\ExternalUserManagement\Policies;

use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Verzeichnisquellen (LDAP/OIDC) entscheiden, wer sich als welches Konto anmeldet: Eine Quelle
 * verknüpft Logins per E-Mail mit bestehenden Konten und vergibt eine Default-Rolle. Anlegen,
 * Ändern (inkl. Aktivieren) und Löschen ist deshalb artwork-Admins vorbehalten – „Tool-Einstellungen
 * ändern“ reicht nicht, sonst ließe sich über einen eigenen LDAP-Host/IdP jedes Konto übernehmen.
 * Ansehen, Verbindung testen und Sync anstoßen bleiben bei „Tool-Einstellungen ändern“
 * (GeneralSettingsPolicy::view).
 */
class ExternalUserSourcePolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasRole(RoleEnum::ARTWORK_ADMIN->value);
    }
}
