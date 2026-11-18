<?php

namespace Artwork\Modules\Chat\Policies;

use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChatPolicy
{
    use HandlesAuthorization;

    /**
     * Nur Mitglieder eines Chats dürfen ihn lesen/beschreiben.
     * Achtung: Artwork-Admins passieren jede Policy über Gate::before im AuthServiceProvider. Die
     * Chat-Routen prüfen deshalb direkt über Chat::hasMember() und nutzen diese Policy nicht.
     */
    public function view(User $user, Chat $chat): bool
    {
        return $chat->users()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, Chat $chat): bool
    {
        return $this->view($user, $chat);
    }

    public function delete(User $user, Chat $chat): bool
    {
        return $this->view($user, $chat);
    }
}
