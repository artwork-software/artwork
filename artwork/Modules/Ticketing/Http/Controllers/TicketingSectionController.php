<?php

namespace Artwork\Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Ticketing\Http\Controllers\Concerns\CallsTickets;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Artwork\Modules\Ticketing\Services\TicketingSalesService;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Hauptmenü → Artwork-Tickets: Verkäufe sowie verkaufte Karten und Abos, gelesen aus tickets. */
class TicketingSectionController extends Controller
{
    use CallsTickets;

    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketingSalesService $sales,
    ) {
    }

    public function sales(Request $request): Response
    {
        $query = $request->validate([
            'days' => ['nullable', 'in:7,30,90'],
            'from' => ['nullable', 'date_format:Y-m-d', 'required_with:to', 'before_or_equal:to'],
            'to' => ['nullable', 'date_format:Y-m-d', 'required_with:from'],
            'events' => ['nullable', 'array', 'max:100'],
            'events.*' => ['uuid'],
        ]);

        return $this->render(
            'Ticketing/Sales',
            ['filters' => $query],
            'sales',
            fn ($connection): array => $this->sales->sales($connection, $query),
        );
    }

    public function passes(Request $request): Response
    {
        $filters = $request->validate([
            'tab' => ['nullable', 'in:passes,subscriptions'],
            'q' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'in:running,pastDue,ended'],
        ]);
        $tab = $filters['tab'] ?? 'passes';
        $search = $filters['q'] ?? '';

        return $this->render(
            'Ticketing/Passes',
            ['filters' => ['tab' => $tab] + $filters],
            $tab,
            fn ($connection): array => $tab === 'passes'
                ? $this->sales->passes($connection, $search)
                : $this->sales->subscriptions($connection, ['state' => $filters['state'] ?? null, 'q' => $search]),
        );
    }

    /**
     * Die Seite mit dem, was tickets liefert; ohne Verbindung oder bei einem Fehler bleibt sie leer und sagt es.
     *
     * @param array<string, mixed> $props
     * @param Closure(TicketingConnection): array<mixed> $load
     */
    private function render(string $component, array $props, string $key, Closure $load): Response
    {
        $connection = $this->connections->current();
        [$props[$key], $ticketsError] = $this->readFromTickets($connection, $load, null);

        return Inertia::render($component, $props + [
            'connected' => $connection !== null,
            'ticketsError' => $ticketsError,
        ]);
    }
}
