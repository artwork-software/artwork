<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Ticketing\Models\TicketingConnection;

/**
 * Automatische Rabatte des Ticket-Hauses: Frühbucher, Staffel (ab N Terminen einer Buchung) und Pakete
 * (N Termine zusammen zu einem Preis), Rabattcodes ohne Code, die der Shop von selbst anwendet. Wert in
 * Basispunkten (Prozent) oder Cent (Betrag, Paketpreis), Tage nach der Uhr des Hauses, der letzte inklusive.
 */
class TicketingDiscountService
{
    public function __construct(private readonly TicketsClient $tickets)
    {
    }

    /**
     * @return array{
     *     discounts: list<array<string, mixed>>,
     *     productions: list<array{id: string, title: string}>,
     *     groups: list<array<string, mixed>>,
     * }
     */
    public function overview(TicketingConnection $connection): array
    {
        $response = $this->tickets->get($connection, '/discounts');

        return [
            'discounts' => $response['discounts'] ?? [],
            'productions' => $response['productions'] ?? [],
            'groups' => $response['groups'] ?? [],
        ];
    }

    /** @param array<string, mixed> $discount */
    public function create(TicketingConnection $connection, array $discount): void
    {
        $this->tickets->post($connection, '/discounts', $discount);
    }

    /** @param array<string, mixed> $discount */
    public function update(TicketingConnection $connection, string $discountId, array $discount): void
    {
        $this->tickets->put($connection, "/discounts/{$discountId}", $discount);
    }

    public function delete(TicketingConnection $connection, string $discountId): void
    {
        $this->tickets->delete($connection, "/discounts/{$discountId}");
    }
}
