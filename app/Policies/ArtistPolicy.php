<?php

namespace App\Policies;

use App\Policies\Concerns\ChecksProjectAccess;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\User\Models\User;

/**
 * Künstler*innen-Stammdaten: Lesen/Export für alle mit Projektzugriff, Verwalten für alle mit
 * Projekt-Schreibrecht (wie Aufenthalte). Admins passieren via Gate::before.
 */
class ArtistPolicy
{
    use ChecksProjectAccess;

    public function viewAny(User $user): bool
    {
        return $this->hasAnyProjectAccess($user);
    }

    public function view(User $user, Artist $artist): bool
    {
        return $artist->exists && $this->hasAnyProjectAccess($user);
    }

    public function export(User $user): bool
    {
        return $this->hasAnyProjectAccess($user);
    }

    public function create(User $user): bool
    {
        return $this->hasAnyProjectWriteAccess($user);
    }

    public function update(User $user, Artist $artist): bool
    {
        return $artist->exists && $this->hasAnyProjectWriteAccess($user);
    }

    public function delete(User $user, Artist $artist): bool
    {
        return $artist->exists && $this->hasAnyProjectWriteAccess($user);
    }

    public function restore(User $user, Artist $artist): bool
    {
        return $artist->exists && $this->hasAnyProjectWriteAccess($user);
    }

    public function forceDelete(User $user, Artist $artist): bool
    {
        return $artist->exists && $this->hasAnyProjectWriteAccess($user);
    }
}
