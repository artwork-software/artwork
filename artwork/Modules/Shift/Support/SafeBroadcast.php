<?php

namespace Artwork\Modules\Shift\Support;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\DB;
use Psr\Http\Client\NetworkExceptionInterface;
use Throwable;

/**
 * Live-Updates nach bereits gespeicherten Änderungen: Die Broadcasts sind ShouldBroadcastNow — ist der
 * WebSocket-Server nicht erreichbar, würde die Exception die Antwort zu einer 500 machen, obwohl die
 * Daten schon gespeichert sind (Nutzer:innen wiederholen dann und erzeugen Duplikate). Fehler werden
 * daher nur gemeldet; die eigene Ansicht aktualisiert sich über die Antwort des Requests.
 *
 * - Kurzschluss: nach dem ersten Transportfehler (Server nicht erreichbar/Timeout, oder ein Versuch dauerte
 *   länger als SLOW_ATTEMPT_SECONDS) im selben Request/Job keine weiteren Versuche – ein hängender
 *   WebSocket-Server kostet sonst je Broadcast den vollen Timeout. Fehler eines einzelnen Events (ungültige
 *   oder zu große Nutzlast, Fehler in broadcastWith) werden nur gemeldet und unterdrücken die übrigen nicht.
 *   Der Zustand liegt request-scoped im Container (SafeBroadcastCircuit), nie statisch – Octane/Swoole hält
 *   statische Werte über Requests.
 * - In einer offenen Transaktion wird erst nach dem Commit gesendet (keine Locks während des Broadcasts,
 *   kein Live-Update für zurückgerollte Daten); der Commit-Callback ist selbst abgesichert.
 */
final class SafeBroadcast
{
    /** Ein so langsamer Fehlversuch gilt als hängender Server, unabhängig von der Fehlermeldung. */
    private const SLOW_ATTEMPT_SECONDS = 1.0;

    /** Kennzeichen von Verbindungs-/Timeout-Fehlern (Pusher-SDK reicht Guzzle-Fehler nur als Text weiter). */
    private const TRANSPORT_ERROR_MARKERS = [
        'curl error',
        'could not resolve host',
        'failed to connect',
        'connection refused',
        'connection reset',
        'connection timed out',
        'operation timed out',
        'timed out',
    ];

    /**
     * @param bool $toOthers wie broadcast(...)->toOthers(): nicht an die auslösende Verbindung senden
     */
    public static function send(object $event, bool $toOthers = false): void
    {
        if ($toOthers && method_exists($event, 'dontBroadcastToCurrentUser')) {
            // Socket-ID jetzt aus dem laufenden Request übernehmen
            $event->dontBroadcastToCurrentUser();
        }

        // Ohne offene Transaktion führt afterCommit den Callback sofort aus
        DB::afterCommit(static function () use ($event): void {
            self::dispatch($event);
        });
    }

    private static function dispatch(object $event): void
    {
        $circuit = app(SafeBroadcastCircuit::class);
        if ($circuit->isOpen()) {
            return;
        }

        $startedAt = microtime(true);
        try {
            broadcast($event);
        } catch (Throwable $exception) {
            if (self::isTransportFailure($exception) || microtime(true) - $startedAt > self::SLOW_ATTEMPT_SECONDS) {
                $circuit->open();
            }
            report($exception);
        }
    }

    /**
     * Server nicht erreichbar oder Timeout – irgendwo in der Exception-Kette.
     */
    private static function isTransportFailure(Throwable $exception): bool
    {
        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            if ($current instanceof ConnectException || $current instanceof NetworkExceptionInterface) {
                return true;
            }

            $message = strtolower($current->getMessage());
            foreach (self::TRANSPORT_ERROR_MARKERS as $marker) {
                if (str_contains($message, $marker)) {
                    return true;
                }
            }
        }

        return false;
    }
}
