<?php

namespace App\Policies;

use App\Policies\Concerns\ChecksProjectAccess;
use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;

/**
 * Unterkünfte sind Stammdaten des Aufenthalts-Moduls und zugleich CRM-Kontakte: Lesen für alle
 * mit Projektzugriff oder CRM-Leserecht, Verwalten für alle mit Projekt-Schreibrecht oder
 * CRM-Verwaltungsrecht. Admins passieren via Gate::before.
 */
class AccommodationPolicy
{
    use ChecksProjectAccess;

    public function viewAny(User $user): bool
    {
        return $this->canRead($user);
    }

    public function view(User $user, Accommodation $accommodation): bool
    {
        return $accommodation->exists && $this->canRead($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Accommodation $accommodation): bool
    {
        return $accommodation->exists && $this->canManage($user);
    }

    public function delete(User $user, Accommodation $accommodation): bool
    {
        return $accommodation->exists && $this->canManage($user);
    }

    public function restore(User $user, Accommodation $accommodation): bool
    {
        return $accommodation->exists && $this->canManage($user);
    }

    public function forceDelete(User $user, Accommodation $accommodation): bool
    {
        return $accommodation->exists && $this->canManage($user);
    }

    private function canRead(User $user): bool
    {
        return $user->can(PermissionEnum::CRM_VIEW->value) || $this->hasAnyProjectAccess($user);
    }

    private function canManage(User $user): bool
    {
        return $user->can(PermissionEnum::CRM_MANAGER->value) || $this->hasAnyProjectWriteAccess($user);
    }
}
