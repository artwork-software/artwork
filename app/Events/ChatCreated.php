<?php

namespace App\Events;


use Artwork\Modules\Chat\Models\Chat;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ChatCreated implements ShouldBroadcast
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
        return [
            'chat' => $this->chat,
        ];
    }
}
