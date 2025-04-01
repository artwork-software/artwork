<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Artwork\Modules\Chat\Models\ChatMessage;

class MessageRead implements ShouldBroadcastNow
{
    use SerializesModels;

    public $messageId;
    public $readerId;
    public $chatId;
    public $readAt;

    public function __construct(ChatMessage $message, int $readerId, $readAt)
    {
        $this->messageId = $message->id;
        $this->readerId = $readerId;
        $this->chatId = $message->chat_id;
        $this->readAt = $readAt;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->chatId);
    }

    public function broadcastAs()
    {
        return 'message.read';
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->messageId,
            'reader_id' => $this->readerId,
            'read_at' => $this->readAt,
        ];
    }
}

