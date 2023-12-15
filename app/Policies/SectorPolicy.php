<?php

namespace App\Policies;

use App\Models\Sector;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SectorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('manage categories_etc');
    }

    public function create(User $user): bool
    {
        return $user->can('manage categories_etc');
    }

    public function update(User $user): bool
    {
        return $user->can('manage categories_etc');
    }

    public function delete(User $user): bool
    {
        return $user->can('manage categories_etc');
    }

    public function restore(): void
    {
    }

    public function forceDelete(): void
    {
    }
}
