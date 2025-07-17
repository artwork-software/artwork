<?php

namespace App\Policies;

use Artwork\Modules\Contacts\Models\Contact;
use Artwork\Modules\User\Models\User;

class ContactPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow viewing if the user is authenticated
        return $user->exists;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Contact $contact): bool
    {
        // Check if the user is authenticated and has access to the contact
        return $user->exists && $contact->exists;
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
    public function update(User $user, Contact $contact): bool
    {
        return $user->exists && $contact->exists;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contact $contact): bool
    {
        return $user->exists && $contact->exists;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Contact $contact): bool
    {
        return $user->exists && $contact->exists;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Contact $contact): bool
    {
        return $user->exists && $contact->exists;
    }
}
