<?php

namespace Tests\Concerns;

use Artwork\Modules\User\Models\User;

/**
 * Setzt die nur per Konsole vergebbare KI-Freischaltung (User::grantAiAccess), ohne die
 * Maschinen-API und KI-Routen gesperrt bzw. unsichtbar sind.
 */
trait GrantsAiAccess
{
    protected function grantAiAccess(User $user): User
    {
        $user->grantAiAccess();

        return $user;
    }
}
