<?php

namespace Artwork\Modules\System\ApiManagement\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorisierung für Passport-Maschinen-Tokens.
 *
 * Vorher war Token::class auf die Settings-Klasse GeneralSettings gemappt statt auf deren Policy;
 * die Prüfung lief damit ins Leere und ausschließlich Admins kamen über Gate::before durch. Wer
 * Tooleinstellungen ändern darf, sieht die Token-Liste seit jeher — darf sie jetzt auch verwalten.
 *
 * Zu beachten: Ein Maschinen-Token authentifiziert als der erstellende Benutzer. Er sollte deshalb
 * von einem Konto angelegt werden, dessen Rechte dem gewünschten Zugriff entsprechen.
 */
class TokenPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->can(PermissionEnum::SETTINGS_UPDATE->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::SETTINGS_UPDATE->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionEnum::SETTINGS_UPDATE->value);
    }
}
