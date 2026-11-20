<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Modules\Event\Events\RoomRequestsChanged;
use Artwork\Modules\Shift\Support\SafeBroadcast;
use Illuminate\Contracts\Foundation\Application;
use Throwable;

/**
 * Meldet der Seite "Eingehende Anfragen", dass sich Raumbelegungsanfragen geändert haben: höchstens
 * ein Hinweis je Request, gesendet nach der Antwort (eine Serie mit 100 Terminen löst einen Hinweis aus).
 * In Befehlen und Jobs gibt es kein Request-Ende; dort höchstens ein Hinweis je Sekunde.
 */
class RoomRequestInboxNotifier
{
    private bool $pending = false;

    private float $lastSentAt = 0.0;

    public function __construct(private readonly Application $app)
    {
    }

    public function notify(): void
    {
        if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
            if (microtime(true) - $this->lastSentAt >= 1.0) {
                $this->send();
            }

            return;
        }

        if ($this->pending) {
            return;
        }

        $this->pending = true;
        $this->app->terminating(function (): void {
            $this->pending = false;
            $this->send();
        });
    }

    private function send(): void
    {
        $this->lastSentAt = microtime(true);
        // Nach der Antwort darf nichts mehr werfen
        try {
            SafeBroadcast::send(new RoomRequestsChanged());
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
