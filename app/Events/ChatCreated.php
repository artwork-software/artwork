<?php

namespace App\Events;

use Artwork\Modules\Chat\Models\Chat;
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
        // NEU: Felder für Listeneintrag konsistent mitsenden
        $chat = $this->chat;
        $chat->setAttribute('last_message', null);
        $chat->setAttribute('unread_count', 0);

        return [
            'chat' => $chat,
        ];
    }
}
