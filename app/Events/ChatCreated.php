<?php

namespace App\Events;

use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\User\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ChatCreated implements ShouldBroadcastNow
{
    use SerializesModels;

    public $chat;

    public function __construct(Chat $chat)
    {
        $this->chat = $chat->load('users');
    }

    public function broadcastOn()
    {
        return $this->chat->users->map(fn($user) => new PrivateChannel('user.' . $user->id))->toArray();
    }

    public function broadcastAs()
    {
        return 'chat.created';
    }

    public function broadcastWith()
    {
        // Felder für den Listeneintrag; ein neuer Chat hat noch keine Nachricht. last_message explizit
        // überschreiben: der Accessor am Chat lieferte sonst die Nachricht samt vollem Absender-Model.
        // Teilnehmende nur als festes Mini-Array (siehe participantPayload).
        return [
            'chat' => array_merge($this->chat->withoutRelations()->setAppends([])->toArray(), [
                'last_message' => null,
                'unread_count' => 0,
                'users' => $this->chat->users
                    ->map(fn (User $user): array => self::participantPayload($user))
                    ->values()
                    ->all(),
            ]),
        ];
    }

    /**
     * Was das Chat-Fenster über Teilnehmende braucht. Keine Kontaktdaten: die Maskierung im User-Model
     * richtet sich nach der auslösenden Person, ein Broadcast geht aber an alle Mitglieder.
     *
     * @return array{
     *     id: int,
     *     first_name: string|null,
     *     last_name: string|null,
     *     full_name: string,
     *     profile_photo_url: string|null
     * }
     */
    public static function participantPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'profile_photo_url' => $user->profile_photo_url,
        ];
    }
}
