<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\User\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Was der Bereich "Artwork-Tickets" im Hauptmenü zeigt, ohne ins Ticket-Dashboard zu wechseln: die
 * Verkäufe eines Zeitraums und die Karten und Abos, die Leute halten. Die Zahlen sind die des Dashboards.
 * Karten und Abos lassen sich hier auch von Hand ändern; die Regeln dafür liegen in tickets.
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

    /**
     * Mehrfachkarten und Abos in einer Liste; ein Abo ist eine Zeile, seine Karte gehört dazu.
     *
     * @param array{kind?: string, state?: string, q?: string} $filter
     * @return list<array<string, mixed>>
     */
    public function holdings(TicketingConnection $connection, array $filter): array
    {
        return $this->tickets->get($connection, '/holdings', array_filter($filter))['holdings'] ?? [];
    }

    /**
     * Eine Karte mit ihrem Verlauf: Einlösungen, Stempel und Korrekturen von Hand.
     *
     * @return array<string, mixed>
     */
    public function pass(TicketingConnection $connection, string $passId): array
    {
        return $this->tickets->get($connection, "/passes/{$passId}")['pass'] ?? [];
    }

    /**
     * Eine Korrektur von Hand, in tickets unter der artwork-Person verbucht. Antwortet mit der Karte, wie
     * sie danach steht.
     *
     * @param array<string, mixed> $command
     * @return array<string, mixed>
     */
    public function correctPass(TicketingConnection $connection, string $passId, array $command, User $actor): array
    {
        if ($command['kind'] === 'validUntil' && ($this->pass($connection, $passId)['subscriptionId'] ?? null) !== null) {
            throw ValidationException::withMessages([
                'day' => __('The pass of a subscription follows its paid periods, so its last day cannot be set by hand.'),
            ]);
        }

        return $this->tickets->post($connection, "/passes/{$passId}/commands", [
            'command' => $command,
            'actor' => $this->tickets->actor($actor),
        ])['pass'] ?? [];
    }

    /**
     * Kündigen, sofort beenden, pausieren, die Pause beenden oder eine Kündigung zurücknehmen, in tickets
     * unter der artwork-Person verbucht. Die E-Mail an die Person verschickt tickets.
     *
     * @param array<string, mixed> $command
     */
    public function changeSubscription(
        TicketingConnection $connection,
        string $subscriptionId,
        array $command,
        User $actor,
    ): void {
        $this->tickets->post($connection, "/subscriptions/{$subscriptionId}/commands", [
            'command' => $command,
            'actor' => $this->tickets->actor($actor),
        ]);
    }
}
