<?php

namespace Artwork\Modules\AppApi\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Ticketing\Services\TicketingDoorService;
use Artwork\Modules\Ticketing\Services\TicketingReleaseService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;

/**
 * The door of Artwork-Tickets in the app: released dates, their guest list, check-in and pass
 * admission. tickets stays the source of truth; this maps its payloads onto the app contract.
 */
class AppTicketingService
{
    public function __construct(
        private readonly TicketingReleaseService $releases,
        private readonly TicketingDoorService $door,
    ) {
    }

    /**
     * Released dates in the range the user may see, with their counters. A date tickets no longer
     * knows is left out.
     *
     * @return list<array<string, mixed>>
     */
    public function dates(User $user, Carbon $start, Carbon $end): array
    {
        $events = $this->releases->releasedVisibleTo($user, $start, $end);
        $summaries = $this->releases->summaries($events);

        return $events
            ->filter(static fn (Event $event): bool => isset($summaries[$event->id]))
            ->map(fn (Event $event): array => $this->datePayload($event) + [
                'cancelled' => $summaries[$event->id]['cancelled'],
                'capacity' => $summaries[$event->id]['capacity'],
                'sold' => $summaries[$event->id]['sold'],
                'checked_in' => $summaries[$event->id]['checked_in'],
            ])
            ->values()
            ->all();
    }

    /**
     * One released date with its guest list; null when it is not released.
     *
     * @return array<string, mixed>|null
     */
    public function show(Event $event): ?array
    {
        $sales = $this->releases->sales($event);

        if (!$sales['released']) {
            return null;
        }

        $event->loadMissing(['project', 'room']);

        return $this->datePayload($event) + [
            'cancelled' => $sales['status'] === 'cancelled',
            'capacity' => (int) $sales['capacity'],
            'sold' => (int) $sales['sold'],
            'checked_in' => (int) $sales['checkedInCount'],
            'doors_at' => $sales['doorsAt'],
            'ticket_count' => (int) $sales['ticketCount'],
            'tickets' => array_map(fn (array $ticket): array => [
                'id' => $ticket['id'],
                'code' => $ticket['code'],
                'status' => $ticket['status'],
                'holder_name' => $ticket['holderName'],
                'email' => $ticket['email'],
                'category_name' => $ticket['categoryName'],
                'reduction_name' => $ticket['reductionName'],
                'requires_proof' => (bool) $ticket['requiresProof'],
                'seat' => $ticket['seat'],
                'checked_in_at' => $ticket['checkedInAt'],
            ], $sales['tickets']),
        ];
    }

    /**
     * @param array{ticketId: string}|array{payload: string} $attempt
     * @return array<string, mixed>
     */
    public function checkIn(Event $event, array $attempt, User $actor, string $scanId): array
    {
        $result = $this->door->checkIn($event, $attempt, $actor, $scanId);

        return [
            'scan_id' => $result['scanId'],
            'outcome' => $result['outcome'],
            'ticket' => $result['ticket'] === null ? null : $this->checkedTicket($result['ticket']),
        ];
    }

    public function revert(Event $event, string $ticketId, User $actor): void
    {
        $this->door->revert($event, $ticketId, $actor);
    }

    /**
     * @param array{q: string}|array{payload: string} $match
     * @return array<string, mixed>
     */
    public function passes(Event $event, array $match): array
    {
        $result = $this->door->passes($event, $match);

        return [
            'seated' => (bool) $result['seated'],
            'categories' => array_map(static fn (array $category): array => [
                'id' => $category['id'],
                'name' => $category['name'],
                'price_cents' => (int) $category['priceCents'],
                'available' => (int) $category['available'],
            ], $result['categories']),
            'passes' => array_map(static fn (array $pass): array => [
                'id' => $pass['id'],
                'code' => $pass['code'],
                'name' => $pass['name'],
                'holder_name' => $pass['holderName'],
                'holder_email' => $pass['holderEmail'],
                'subscription' => (bool) $pass['subscription'],
                'uses_left' => (int) $pass['usesLeft'],
                'valid_until' => $pass['validUntil'],
                'block' => $pass['block'],
                'to_pay' => array_map(static fn (array $price): array => [
                    'price_category_id' => $price['priceCategoryId'],
                    'cents' => (int) $price['cents'],
                ], $pass['toPay']),
            ], $result['passes']),
        ];
    }

    /** @return array{order_number: string, ticket_code: string, paid_cents: int, checked_in_at: ?string} */
    public function admit(
        Event $event,
        string $admissionId,
        string $passId,
        string $priceCategoryId,
        User $actor,
    ): array {
        $result = $this->door->admit($event, $admissionId, $passId, $priceCategoryId, $actor);

        return [
            'order_number' => $result['orderNumber'],
            'ticket_code' => $result['ticketCode'],
            'paid_cents' => (int) $result['paidCents'],
            'checked_in_at' => $result['checkedInAt'],
        ];
    }

    /** @return array<string, mixed> */
    private function datePayload(Event $event): array
    {
        return [
            'event_id' => $event->id,
            'name' => $event->eventName ?: null,
            'project' => ['id' => $event->project->id, 'name' => $event->project->name],
            'room' => $event->room?->name,
            'start' => $event->start_time->toIso8601String(),
            'end' => $event->end_time?->toIso8601String(),
        ];
    }

    /**
     * @param array<string, mixed> $ticket
     * @return array<string, mixed>
     */
    private function checkedTicket(array $ticket): array
    {
        return [
            'id' => $ticket['id'],
            'code' => $ticket['code'],
            'holder_name' => $ticket['holderName'],
            'category_name' => $ticket['categoryName'],
            'requires_proof' => (bool) $ticket['requiresProof'],
            'seat' => $ticket['seat'],
            'checked_in_at' => $ticket['checkedInAt'],
        ];
    }
}
