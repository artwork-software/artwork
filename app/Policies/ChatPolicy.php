<?php

namespace App\Policies;

use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\User\Models\User;

class ChatPolicy
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
    public function view(User $user, Chat $chat): bool
    {
        // Check if the user is part of the chat or has permission to view it
        return $user->exists && $chat->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Allow creation if the user is authenticated
        return $user->exists;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Chat $chat): bool
    {
        // Allow update if the user is part of the chat or has permission to update it
        return $user->exists && $chat->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Chat $chat): bool
    {
        // Allow deletion if the user is part of the chat or has permission to delete it
        return $user->exists && $chat->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Chat $chat): bool
    {
        return $user->exists && $chat->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Chat $chat): bool
    {
        // Allow permanent deletion if the user is part of the chat or has permission to delete it
        return $user->exists && $chat->users()->where('user_id', $user->id)->exists();
    }
}
