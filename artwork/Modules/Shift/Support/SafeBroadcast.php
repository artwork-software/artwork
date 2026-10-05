<?php

namespace Artwork\Modules\Shift\Support;

use Throwable;

/**
 * Live-Updates nach bereits gespeicherten Änderungen: Die Broadcasts sind ShouldBroadcastNow — ist der
 * WebSocket-Server nicht erreichbar, würde die Exception die Antwort zu einer 500 machen, obwohl die
 * Daten schon gespeichert sind (Nutzer:innen wiederholen dann und erzeugen Duplikate). Fehler werden
 * daher nur gemeldet; die eigene Ansicht aktualisiert sich über die Antwort des Requests.
 */
final class SafeBroadcast
{
    public static function send(object $event): void
    {
        try {
            broadcast($event);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
