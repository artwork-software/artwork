<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Collection;

/**
 * Das Team des Ticket-Hauses, aus Sicht dieses artworks: wer von hier schon drüben ist, und
 * ein paar Leute auf einmal einladen. Die Einladung selbst schreibt und verschickt tickets —
 * wer sie öffnet, landet im selben Ablauf wie jemand, der von der Team-Seite dort eingeladen
 * wurde. Absender der Mail ist, wer hier auf den Knopf gedrückt hat.
 */
class TicketingTeamService
{
    public function __construct(private readonly TicketsClient $tickets)
    {
    }

    /**
     * Die Personen dieses artworks mit ihrem Stand drüben: Mitglied, eingeladen, oder nichts davon.
     *
     * @return Collection<int, array{id: int, name: string, email: string, status: string|null, preset: string|null}>
     */
    public function people(TicketingConnection $connection): Collection
    {
        $team = collect($this->tickets->get($connection, '/team')['team'] ?? [])
            ->keyBy(static fn (array $entry): string => strtolower((string) $entry['email']));

        return User::query()
            ->select(['id', 'first_name', 'last_name', 'email'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(static function (User $user) use ($team): array {
                $entry = $team->get(strtolower((string) $user->email));

                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'status' => $entry['status'] ?? null,
                    'preset' => $entry['preset'] ?? ($entry === null ? null : 'custom'),
                ];
            })
            ->values();
    }

    /**
     * @param list<int> $userIds
     * @return list<array{email: string, status: string}>
     */
    public function invite(TicketingConnection $connection, User $invitedBy, array $userIds, string $preset): array
    {
        $users = User::query()->whereIn('id', $userIds)->get(['id', 'email']);

        $response = $this->tickets->post($connection, '/team/invitations', [
            'invitedBy' => ['email' => $invitedBy->email, 'name' => $invitedBy->full_name],
            'invitations' => $users
                ->map(static fn (User $user): array => ['email' => $user->email, 'preset' => $preset])
                ->values()
                ->all(),
        ]);

        return $response['invitations'] ?? [];
    }
}
