<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Ticketing\Models\TicketingConnection;

/**
 * Mehrfachkarten und Abos des Ticket-Hauses, von hier aus gepflegt. Ein Typ reist in der Form,
 * in der tickets ihn schickt und zurücknimmt: Beträge in Cent, Tage und Uhrzeiten nach der Uhr
 * des Hauses. Die Regeln bleiben die von tickets — ist etwas verkauft, bleibt fest, was die
 * Inhaber gekauft haben; löschen geht nur, solange nichts verkauft ist.
 */
class TicketingPassTypeService
{
    public function __construct(private readonly TicketsClient $tickets)
    {
    }

    /**
     * @return array{
     *     passTypes: list<array<string, mixed>>,
     *     productions: list<array{id: string, title: string}>,
     *     groups: list<array{id: string, name: string, eventIds: list<string>, sold: bool}>,
     * }
     */
    public function overview(TicketingConnection $connection): array
    {
        $response = $this->tickets->get($connection, '/pass-types');

        return [
            'passTypes' => $response['passTypes'] ?? [],
            'productions' => $response['productions'] ?? [],
            'groups' => $response['groups'] ?? [],
        ];
    }

    /** @param array<string, mixed> $passType */
    public function create(TicketingConnection $connection, array $passType): void
    {
        $this->tickets->post($connection, '/pass-types', $passType);
    }

    /** @param array<string, mixed> $passType */
    public function update(TicketingConnection $connection, string $passTypeId, array $passType): void
    {
        $this->tickets->put($connection, "/pass-types/{$passTypeId}", $passType);
    }

    public function publish(TicketingConnection $connection, string $passTypeId): void
    {
        $this->tickets->post($connection, "/pass-types/{$passTypeId}/publish");
    }

    /** Beendet den Verkauf; ausgegebene Karten und laufende Abos bleiben gültig. */
    public function archive(TicketingConnection $connection, string $passTypeId): void
    {
        $this->tickets->post($connection, "/pass-types/{$passTypeId}/archive");
    }

    public function delete(TicketingConnection $connection, string $passTypeId): void
    {
        $this->tickets->delete($connection, "/pass-types/{$passTypeId}");
    }
}
