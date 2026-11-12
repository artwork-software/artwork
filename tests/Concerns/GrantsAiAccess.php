<?php

namespace Tests\Concerns;

use Artwork\Modules\Permission\Enums\ConsoleOnlyPermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\User\Models\User;

/**
 * Vergibt das Konsolen-Recht "ai access", ohne das Maschinen-API und KI-Routen mit 403 antworten
 * (EnsureAiAccessPermission).
 */
trait GrantsAiAccess
{
    protected function grantAiAccess(User $user): User
    {
        $user->givePermissionTo(Permission::findOrCreate(ConsoleOnlyPermissionEnum::AiAccess->value, 'web'));
        $user->unsetRelation('permissions');

        return $user;
    }
}
