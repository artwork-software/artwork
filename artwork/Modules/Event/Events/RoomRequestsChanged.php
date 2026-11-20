<?php

namespace Artwork\Modules\Event\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Hinweis ohne Inhalt: eine Raumbelegungsanfrage ist neu, geändert oder beantwortet. Die Seite
 * "Eingehende Anfragen" lädt daraufhin ihre Liste neu; welche Anfragen jemand sieht, entscheidet der Server.
 */
class RoomRequestsChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;

    public const string CHANNEL = 'room-requests.inbox';

    public function broadcastAs(): string
    {
        return 'room-requests.changed';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(self::CHANNEL);
    }

    /**
     * @return array<string, never>
     */
    public function broadcastWith(): array
    {
        return [];
    }
}
