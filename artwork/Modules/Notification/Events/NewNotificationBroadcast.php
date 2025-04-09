<?php

namespace Artwork\Modules\Notification\Events;

use Artwork\Modules\User\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Artwork\Modules\Chat\Models\ChatMessage;

class NewNotificationBroadcast implements ShouldBroadcastNow
{
    use SerializesModels;

    public array $message;
    public User $user;

    public function __construct(User $user, array $message)
    {
        $this->user = $user;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('notifications.' . $this->user->id);
    }

    public function broadcastAs()
    {
        return 'incoming-notification';
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message,
        ];
    }
}

