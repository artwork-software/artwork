<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Ticketing\Exceptions\TicketingLockedException;
use Artwork\Modules\Ticketing\Models\TicketingEventRelease;
use Artwork\Modules\Ticketing\Models\TicketingRoomLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Termine im Verkauf werden nicht gelöscht und nur mit Recht und Bestätigung verschoben; Käufer*innen
 * hängen daran. Ohne aktive Verbindung prüft das nichts.
 */
class TicketingLock
{
    /** Setzt das Frontend, nachdem die Person das Verschieben eines Termins im Verkauf bestätigt hat. */
    public const MOVE_CONFIRMED_HEADER = 'X-Ticketing-Move-Confirmed';

    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketingReleaseService $releases,
    ) {
    }

    /** Im Verkauf: Löschen ist gesperrt, Verschieben braucht Recht und Bestätigung. */
    public function isOnSale(Event $event): bool
    {
        return $this->anyReleased(TicketingEventRelease::query()->where('event_id', $event->id));
    }

    public function assertEventDeletable(Event $event): void
    {
        if ($this->isOnSale($event)) {
            throw new TicketingLockedException(
                __('This date is on sale. Withdraw it in the ticketing component of the project first.')
            );
        }
    }

    /**
     * Zeit oder Raum eines Termins im Verkauf ändert nur, wer das Recht dazu hat und es bestätigt hat,
     * und nur in einen Raum, der in tickets verkauft. Ohne angemeldete Person (Jobs, Konsole) prüft das nichts.
     */
    public function assertEventMovable(Event $event): void
    {
        if (Auth::guest() || !$this->isOnSale($event)) {
            return;
        }

        if (Gate::denies(PermissionEnum::TICKETING_MOVE_ON_SALE->value)) {
            throw new TicketingLockedException(
                __('This date is on sale. Only people with the permission "Change dates on sale" can change its time or room.')
            );
        }

        $from = $this->venueOf($event->getOriginal('room_id'));
        $to = $this->venueOf($event->room_id);

        if ($event->isDirty('room_id') && $to === null) {
            throw new TicketingLockedException(
                __('This date is on sale. It can only move to a room that is synced with Artwork-Tickets.')
            );
        }

        if (!request()->headers->has(self::MOVE_CONFIRMED_HEADER)) {
            throw new TicketingLockedException(__('This date is on sale. Confirm the move before saving it.'));
        }

        if ($to !== null && $to !== $from) {
            $this->releases->assertVenueChangeable($event, $from, $to);
        }
    }

    public function assertProjectDeletable(Project $project): void
    {
        $events = Event::query()->where('project_id', $project->id)->select('id');

        if ($this->anyReleased(TicketingEventRelease::query()->whereIn('event_id', $events))) {
            throw new TicketingLockedException(
                __('This project has dates on sale. Withdraw them in its ticketing component first.')
            );
        }
    }

    private function venueOf(?int $roomId): ?string
    {
        return $roomId === null ? null : TicketingRoomLink::query()->where('room_id', $roomId)->value('venue_id');
    }

    /** @param Builder<TicketingEventRelease> $releases */
    private function anyReleased(Builder $releases): bool
    {
        return $this->connections->isActive()
            && $releases->where('state', TicketingEventRelease::STATE_RELEASED)->exists();
    }
}
