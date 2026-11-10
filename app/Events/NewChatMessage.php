<?php

namespace App\Events;

use Artwork\Modules\Chat\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcastNow
{
    use InteractsWithSockets;
    use SerializesModels;

    public $message;
    public $chatId;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
        $this->chatId = $message->chat_id;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->chatId);
    }

    /**
     * Absender:in nur als festes Mini-Array: die Maskierung privater Kontaktdaten im User-Model
     * richtet sich nach der auslösenden Person, nicht nach den Empfänger:innen des Broadcasts.
     */
    public function broadcastWith()
    {
        $message = $this->message
            ->load(['sender'])
            ->append(['created_at_iso', 'created_at_date', 'created_at_time']);
        $sender = $message->sender;

        return [
            'message' => array_merge($message->withoutRelations()->toArray(), [
                'sender' => $sender ? ChatCreated::participantPayload($sender) : null,
            ]),
        ];
    }

    public function broadcastAs()
    {
        return 'new.message';
    }
}
