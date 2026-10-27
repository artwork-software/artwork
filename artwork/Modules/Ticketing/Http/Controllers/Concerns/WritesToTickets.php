<?php

namespace Artwork\Modules\Ticketing\Http\Controllers\Concerns;

use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Ein Schreibzugriff auf tickets aus einem Einstellungs-Tab, mit der Antwort, die der Tab erwartet:
 * Erfolg als Hinweis, eine Ablehnung von tickets als Meldung.
 */
trait WritesToTickets
{
    /**
     * Für Dialoge: eine Ablehnung kommt als Formularfehler zurück, der Dialog bleibt offen und mit
     * ihm, was eingegeben wurde.
     *
     * @param Closure(TicketingConnection): void $call
     */
    private function saveToTickets(?TicketingConnection $connection, Closure $call): RedirectResponse
    {
        $connection ??= throw ValidationException::withMessages([
            'tickets' => __('This installation is not connected to Artwork-Tickets.'),
        ]);

        try {
            $call($connection);
        } catch (TicketingConnectionException $exception) {
            throw ValidationException::withMessages(['tickets' => $exception->getMessage()]);
        }

        return back()->with('success', __('Saved in Artwork-Tickets.'));
    }

    /**
     * Für Knöpfe in der Liste: das Ergebnis kommt als Hinweis.
     *
     * @param Closure(TicketingConnection): void $call
     */
    private function sendToTickets(?TicketingConnection $connection, Closure $call, string $success): RedirectResponse
    {
        if (!$connection) {
            return back()->with('error', __('This installation is not connected to Artwork-Tickets.'));
        }

        try {
            $call($connection);
        } catch (TicketingConnectionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $success);
    }
}
