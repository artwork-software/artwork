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
 * hängen daran. Ohne konfiguriertes tickets prüft das nichts.
 */
class TicketingLock
{
    /** Setzt das Frontend, nachdem die Person das Verschieben eines Termins im Verkauf bestätigt hat. */
    public const MOVE_CONFIRMED_HEADER = 'X-Ticketing-Move-Confirmed';

    public function __construct(private readonly TicketingConnectionService $connections)
    {
    }

    public function assertEventDeletable(Event $event): void
    {
        if ($this->anyReleased(TicketingEventRelease::query()->where('event_id', $event->id))) {
            throw new TicketingLockedException(
                __('This date is on sale. Withdraw it in the ticketing component of the project first.')
            );
        }
    }

    /**
     * Zeit oder Raum eines Termins im Verkauf ändert nur, wer das Recht dazu hat und es bestätigt hat,
     * und nur innerhalb der Spielstätte, in der verkauft wird. Ohne angemeldete Person (Jobs, Konsole)
     * prüft das nichts.
     */
    public function assertEventMovable(Event $event): void
    {
        if (Auth::guest() || !$this->anyReleased(TicketingEventRelease::query()->where('event_id', $event->id))) {
            return;
        }

        if (Gate::denies(PermissionEnum::TICKETING_MOVE_ON_SALE->value)) {
            throw new TicketingLockedException(
                __('This date is on sale. Only people with the permission "Move dates on sale" can change its time or room.')
            );
        }

        if ($event->isDirty('room_id') && $this->venueOf($event->room_id) !== $this->venueOf($event->getOriginal('room_id'))) {
            throw new TicketingLockedException(
                __('This date is on sale. It can only move to a room that sells as the same venue in artwork tickets.')
            );
        }

        if (!request()->headers->has(self::MOVE_CONFIRMED_HEADER)) {
            throw new TicketingLockedException(__('This date is on sale. Confirm the move before saving it.'));
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
        return $this->connections->isConfigured()
            && $releases->where('state', TicketingEventRelease::STATE_RELEASED)->exists();
    }
}
