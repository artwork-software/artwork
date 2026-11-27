<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Ticketing\Models\TicketingConnection;

/**
 * Gruppen von Produktionen ("Workshops"), für die Mehrfachkarten, Abos und Rabattcodes als Ganzes
 * gelten. Die Mitgliedschaft liest tickets live: Was in eine Gruppe kommt oder sie verlässt, gilt
 * sofort für alles mit der Gruppe, auch für schon verkaufte Karten.
 */
class TicketingProductionGroupService
{
    public function __construct(private readonly TicketsClient $tickets)
    {
    }

    /**
     * @return array{
     *     groups: list<array{
     *         id: string,
     *         name: string,
     *         eventIds: list<string>,
     *         passTypeCount: int,
     *         couponCount: int,
     *         sold: bool,
     *     }>,
     *     productions: list<array{id: string, title: string}>,
     * }
     */
    public function overview(TicketingConnection $connection): array
    {
        $response = $this->tickets->get($connection, '/production-groups');

        return [
            'groups' => $response['groups'] ?? [],
            'productions' => $response['productions'] ?? [],
        ];
    }

    /** @param array{name: string, eventIds: list<string>} $group */
    public function create(TicketingConnection $connection, array $group): void
    {
        $this->tickets->post($connection, '/production-groups', $group);
    }

    /** @param array{name: string, eventIds: list<string>} $group */
    public function update(TicketingConnection $connection, string $groupId, array $group): void
    {
        $this->tickets->put($connection, "/production-groups/{$groupId}", $group);
    }

    public function delete(TicketingConnection $connection, string $groupId): void
    {
        $this->tickets->delete($connection, "/production-groups/{$groupId}");
    }
}
