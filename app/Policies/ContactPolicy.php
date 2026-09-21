<?php

namespace App\Policies;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Contacts\Models\Contact;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ServiceProvider\Policies\ServiceProviderPolicy;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Kontaktdaten hängen polymorph an User, Dienstleister oder Unterkunft. Schreiben darf, wer die
 * Eltern-Entität pflegen darf: eigenes Profil, Externe-Verwaltung (Dienstleister) bzw.
 * AccommodationPolicy::update (Unterkunft). Admins passieren via Gate::before.
 */
class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->exists;
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->exists && $contact->exists;
    }

    public function create(User $user, ?Model $parent = null): bool
    {
        return $parent !== null && $this->canManageParent($user, $parent);
    }

    public function update(User $user, Contact $contact): bool
    {
        return $contact->exists && $this->canManageParent($user, $contact->contactable);
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $contact->exists && $this->canManageParent($user, $contact->contactable);
    }

    public function restore(User $user, Contact $contact): bool
    {
        return $this->delete($user, $contact);
    }

    public function forceDelete(User $user, Contact $contact): bool
    {
        return $this->delete($user, $contact);
    }

    private function canManageParent(User $user, ?Model $parent): bool
    {
        if ($parent instanceof User) {
            return $user->is($parent);
        }

        if ($parent instanceof ServiceProvider) {
            return ServiceProviderPolicy::canManageExternals($user);
        }

        if ($parent instanceof Accommodation) {
            return $user->can('update', $parent);
        }

        return false;
    }
}
