<?php

namespace Tests\Unit\Modules\Shift\Support;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use RuntimeException;

/**
 * Zählt Sende-Versuche (broadcastOn wird beim Senden aufgerufen) und kann einen Verbindungsfehler simulieren.
 * Ohne Kanäle bricht der Versand danach ab – es wird nie ein echter WebSocket-Server angesprochen.
 */
final class SafeBroadcastTestEvent implements ShouldBroadcastNow
{
    public static int $attempts = 0;

    public static bool $fail = false;

    public static string $failureMessage = '';

    public static ?\Throwable $previous = null;

    /**
     * @return array<int, mixed>
     */
    public function broadcastOn(): array
    {
        self::$attempts++;
        if (self::$fail) {
            throw new RuntimeException(self::$failureMessage, 0, self::$previous);
        }

        return [];
    }
}
