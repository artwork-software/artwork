<?php

namespace Artwork\Modules\Chat\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Ein Mitglied hat einen Gruppenchat verlassen. Geht an den Chat-Kanal, damit die übrigen Mitglieder
 * die Teilnehmerliste ohne Neuladen aktualisieren.
 */
class ChatMemberLeft implements ShouldBroadcastNow
{
    public function __construct(
        public int $chatId,
        public int $userId,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat.' . $this->chatId);
    }

    public function broadcastAs(): string
    {
        return 'member.left';
    }

    /**
     * @return array{chat_id: int, user_id: int}
     */
    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chatId,
            'user_id' => $this->userId,
        ];
    }
}
