<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\User\Models\User;

/**
 * Der Einlass eines freigegebenen Termins, aus artwork heraus: Tickets einchecken und Inhaber einer
 * Mehrfachkarte oder eines Abos einlassen, die noch kein Ticket haben. So ein Einlass ist in tickets ein
 * Verkauf an der Abendkasse — ein Ticket, die Kartennutzung darauf, der Rest an der eigenen Kasse des
 * Hauses bezahlt. Wer an der Tür steht, nennt tickets als die Person, die eingelassen hat.
 */
class TicketingDoorService
{
    /** Ein Einlass bucht, löst die Karte ein, zahlt und checkt ein — mehr als ein üblicher Aufruf. */
    private const ADMISSION_TIMEOUT = 20;

    public function __construct(
        private readonly TicketsClient $tickets,
        private readonly TicketingConnectionService $connections,
        private readonly TicketingReleaseService $releases,
        private readonly ProjectComponentVisibilityService $componentVisibility,
    ) {
    }

    /**
     * Wer an die Tür darf: mit Verbindung, dem Einlass-Recht und Sicht auf die Ticketing-Komponente —
     * der Einlass zeigt die Gästeliste mit Namen und E-Mails.
     */
    public function mayRunDoor(User $user): bool
    {
        return $this->connections->current() !== null
            && $user->can(PermissionEnum::TICKETING_ADMISSION->value)
            && $this->componentVisibility->canSeeComponentTypeInProject(
                $user,
                [ProjectTabComponentEnum::TICKETING->value]
            );
    }

    /** Den Einlass eines Termins führt, wer an die Tür darf und sein Projekt sieht. */
    public function mayAdmit(User $user, Event $event): bool
    {
        return $event->project !== null
            && $user->can('view', $event->project)
            && $this->mayRunDoor($user);
    }

    /**
     * Ein gescanntes Ticket oder eines aus der Liste. Mit der Scan-ID der Tür beantwortet tickets
     * eine Wiederholung mit dem ersten Ergebnis, statt zweimal einzulassen.
     *
     * @param array{ticketId: string}|array{payload: string} $attempt
     * @return array{scanId: string, outcome: string, ticket: ?array<string, mixed>}
     */
    public function checkIn(Event $event, array $attempt, User $actor, ?string $scanId = null): array
    {
        [$connection, $dateId] = $this->dateOf($event);

        /** @var array{scanId: string, outcome: string, ticket: ?array<string, mixed>} */
        return $this->tickets->post($connection, "/dates/{$dateId}/check-ins", $attempt + array_filter([
            'scanId' => $scanId,
            'actor' => $this->actor($actor),
        ]));
    }

    public function revert(Event $event, string $ticketId, User $actor): void
    {
        [$connection, $dateId] = $this->dateOf($event);

        $this->tickets->post($connection, "/dates/{$dateId}/check-ins/revert", [
            'ticketId' => $ticketId,
            'actor' => $this->actor($actor),
        ]);
    }

    /**
     * Karten, die die Tür für den Termin nachschlagen oder scannen kann, je mit dem, was sie heute
     * ausschließt, und dem, was je Preisklasse noch zu zahlen ist.
     *
     * @param array{q: string}|array{payload: string} $match
     * @return array<string, mixed>
     */
    public function passes(Event $event, array $match): array
    {
        [$connection, $dateId] = $this->dateOf($event);

        return $this->tickets->get($connection, "/dates/{$dateId}/passes", $match);
    }

    /**
     * Mit der Einlass-ID der Tür beantwortet tickets eine Wiederholung mit dem ersten Einlass,
     * statt die Karte ein zweites Mal einzulösen.
     *
     * @return array{orderNumber: string, ticketCode: string, paidCents: int, checkedInAt: ?string}
     */
    public function admit(
        Event $event,
        string $admissionId,
        string $passId,
        string $priceCategoryId,
        User $actor,
    ): array {
        [$connection, $dateId] = $this->dateOf($event);

        /** @var array{orderNumber: string, ticketCode: string, paidCents: int, checkedInAt: ?string} */
        return $this->tickets->post($connection, "/dates/{$dateId}/pass-admissions", [
            'admissionId' => $admissionId,
            'passId' => $passId,
            'priceCategoryId' => $priceCategoryId,
            'actor' => $this->actor($actor),
        ], self::ADMISSION_TIMEOUT);
    }

    /** @return array{0: TicketingConnection, 1: string} */
    private function dateOf(Event $event): array
    {
        $connection = $this->connections->current();
        $dateId = $event->ticketingRelease?->tickets_date_id;

        if (!$connection || !$dateId || !$this->releases->isReleased($event)) {
            throw new TicketingConnectionException(__('This date is not released for sale.'));
        }

        return [$connection, $dateId];
    }

    /** @return array{email: string, name: string} */
    private function actor(User $user): array
    {
        return ['email' => $user->email, 'name' => $user->full_name];
    }
}
