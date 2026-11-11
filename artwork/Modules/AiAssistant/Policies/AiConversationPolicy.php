<?php

namespace Artwork\Modules\AiAssistant\Policies;

use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\User\Models\User;

/**
 * Eigentuemerpruefung fuer Gespraeche (core-chat-historie; Konzept C3 §2).
 *
 * WICHTIG: Diese Klasse ist bewusst NICHT in AuthServiceProvider::$policies eingetragen und wird
 * direkt aufgerufen, nie ueber $user->can() oder $this->authorize(). Grund: Gate::before in
 * app/Providers/AuthServiceProvider.php gibt Artwork-Admins jede Gate-Abfrage frei — ein
 * registrierter Policy-Aufruf wuerde einem Admin also fremde Gespraeche oeffnen. Ein Gespraech
 * gehoert der Person, die es gefuehrt hat, und sonst niemandem.
 */
class AiConversationPolicy
{
    public function view(User $user, AiConversation $conversation): bool
    {
        return $this->owns($user, $conversation);
    }

    public function update(User $user, AiConversation $conversation): bool
    {
        return $this->owns($user, $conversation);
    }

    public function delete(User $user, AiConversation $conversation): bool
    {
        return $this->owns($user, $conversation);
    }

    private function owns(User $user, AiConversation $conversation): bool
    {
        return $user->id === $conversation->user_id;
    }
}
