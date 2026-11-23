<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Eingehende Anfragen einer Person: welche Raumbelegungsanfragen sie sieht und wie viele Anfragen
 * (Raumbelegung und Terminüberprüfung) offen sind.
 */
class IncomingRequestService
{
    public function __construct(private readonly EventVerificationService $eventVerificationService)
    {
    }

    public function seesAllRoomRequests(User $user): bool
    {
        return $user->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            || $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);
    }

    /**
     * @return Collection<int, int> Räume, die die Person direkt oder über ein Team verwaltet
     */
    public function administeredRoomIds(User $user): Collection
    {
        return Room::query()->administeredBy($user->id)->pluck('id');
    }

    /**
     * Offene Raumbelegungsanfragen, die die Person sieht; null, wenn sie keine sehen darf.
     * Anfragen zu geplanten Terminen erreichen die Raumadmins erst, wenn der Termin ein
     * "richtiger" Termin geworden ist.
     *
     * @return Builder<Event>|null
     */
    public function openRoomRequestsQuery(User $user): ?Builder
    {
        $query = Event::query()
            ->where('occupancy_option', true)
            ->where('is_planning', false)
            ->whereNotNull('room_id');

        if ($this->seesAllRoomRequests($user)) {
            return $query;
        }

        $roomIds = $this->administeredRoomIds($user);

        return $roomIds->isEmpty() ? null : $query->whereIn('room_id', $roomIds);
    }

    /**
     * Zahl der offenen Anfragen für die Anzeige am Reiter und im Seitenmenü.
     */
    public function openCount(User $user): int
    {
        $roomRequests = $this->openRoomRequestsQuery($user)?->count() ?? 0;
        $verifications = $this->eventVerificationService
            ->openRequestsForInbox($user, $user->hasRole(RoleEnum::ARTWORK_ADMIN->value))
            ->count();

        return $roomRequests + $verifications;
    }
}
