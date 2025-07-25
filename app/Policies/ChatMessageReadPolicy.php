<?php

namespace App\Policies;

use Artwork\Modules\Chat\Models\ChatMessageRead;
use Artwork\Modules\User\Models\User;

class ChatMessageReadPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->exists;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ChatMessageRead $chatMessageRead): bool
    {
        return $user->exists && $chatMessageRead->exists;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->exists;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ChatMessageRead $chatMessageRead): bool
    {
        return $user->exists && $chatMessageRead->exists;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ChatMessageRead $chatMessageRead): bool
    {
        return $user->exists && $chatMessageRead->exists;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ChatMessageRead $chatMessageRead): bool
    {
        return $user->exists && $chatMessageRead->exists;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ChatMessageRead $chatMessageRead): bool
    {
        return $user->exists && $chatMessageRead->exists;
    }
}
