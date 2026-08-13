<?php

namespace Artwork\Modules\Webhook\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Webhook-Endpunkte transportieren Daten aus artwork hinaus und tragen ein Signaturgeheimnis.
 * Sie hängen deshalb an einer eigenen Berechtigung statt an den allgemeinen Tooleinstellungen.
 */
class WebhookEndpointPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::WEBHOOKS_MANAGE->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermissionEnum::WEBHOOKS_MANAGE->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::WEBHOOKS_MANAGE->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermissionEnum::WEBHOOKS_MANAGE->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionEnum::WEBHOOKS_MANAGE->value);
    }
}
