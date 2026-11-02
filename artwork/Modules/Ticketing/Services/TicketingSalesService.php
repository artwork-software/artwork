<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Ticketing\Models\TicketingConnection;

/**
 * Was der Bereich "Artwork-Tickets" im Hauptmenü zeigt, ohne ins Ticket-Dashboard zu wechseln: die
 * Verkäufe eines Zeitraums und die Karten und Abos, die Leute halten. Die Zahlen sind die des Dashboards.
 */
class TicketingSalesService
{
    public function __construct(private readonly TicketsClient $tickets)
    {
    }

    /**
     * @param array{days?: string, from?: string, to?: string, events?: list<string>} $query
     * @return array<string, mixed>
     */
    public function sales(TicketingConnection $connection, array $query): array
    {
        return $this->tickets->get($connection, '/sales', array_filter([
            ...$query,
            'events' => implode(',', $query['events'] ?? []),
        ]));
    }

    /** @return list<array<string, mixed>> */
    public function passes(TicketingConnection $connection, string $query): array
    {
        return $this->tickets->get($connection, '/passes', ['q' => $query])['passes'] ?? [];
    }

    /**
     * @param array{state?: string, q?: string} $filter
     * @return list<array<string, mixed>>
     */
    public function subscriptions(TicketingConnection $connection, array $filter): array
    {
        return $this->tickets->get($connection, '/subscriptions', array_filter($filter))['subscriptions'] ?? [];
    }
}
