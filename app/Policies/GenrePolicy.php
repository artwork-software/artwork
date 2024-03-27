<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GenrePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value);
    }

    public function restore(): void
    {
    }

    public function forceDelete(User $user, Genre $genre): void
    {
    }
}
