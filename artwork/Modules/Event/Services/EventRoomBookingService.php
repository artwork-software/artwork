<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomRequestNotificationService;
use Artwork\Modules\User\Models\User;

/**
 * Raumrechte beim Anlegen und Verschieben von Terminen außerhalb des Termin-Dialogs (Kalender-Mehrfach-
 * bearbeitung, Terminliste im Projekt, KI-Assistent): fest buchen, als Raumanfrage anlegen oder gar nicht.
 * Eine Quelle für Oberfläche und KI, damit niemand über die KI mehr darf als selbst.
 */
class EventRoomBookingService
{
    public function __construct(
        private readonly EventSettingsService $eventSettingsService,
        private readonly RoomRequestNotificationService $roomRequestNotificationService,
    ) {
    }

    public function mayBookDirectly(User $user, bool $isPlanning, Room $room): bool
    {
        if ($isPlanning) {
            return $user->can(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value);
        }

        // "Termine immer direkt buchbar": "Raumbelegungen anfragen" bedeutet dann "Termine anlegen"
        // und schließt Bulk-Anlage und Serien ein (Entscheidung 18.09.2026).
        // "Kann von jedem fest gebucht werden" gibt kein Anlegerecht: nur wer laut EventPolicy::create anlegen
        // darf, bucht dort fest (vorher genügte der Schalter allein).
        return $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value)
            || ($this->eventSettingsService->alwaysDirectBooking()
                && $user->can(PermissionEnum::EVENT_REQUEST->value))
            || ($room->everyone_can_book && $user->can('create', [Event::class, $room]))
            || $room->isAdministeredBy($user);
    }

    /**
     * Wer im Raum nicht direkt buchen, aber anfragen darf, legt Raumanfragen an – wie im Termin-Dialog.
     * Geplante Termine und "Termine immer direkt buchbar" kennen keine Raumanfrage.
     *
     * @return bool|null false = fest buchen, true = als Raumanfrage, null = weder noch (keine Berechtigung)
     */
    public function roomRequestRequired(User $user, bool $isPlanning, Room $room): ?bool
    {
        if ($this->mayBookDirectly($user, $isPlanning, $room)) {
            return false;
        }

        // Ohne Anlegerecht auch keine Anfrage (EventPolicy::book allein ließe sie über everyone_can_book zu)
        $mayRequestRoom = !$isPlanning
            && !$this->eventSettingsService->alwaysDirectBooking()
            && $user->can('create', [Event::class, $room])
            && $user->can('book', [Event::class, $room, true, false]);

        return $mayRequestRoom ? true : null;
    }

    /**
     * Verschobene Termine ohne Buchungsrecht im Zielraum werden dort zur Raumanfrage; die Raumadmins
     * erfahren von den neuen Anfragen (bereits offene meldet der Aufrufer als geänderte Anfragen).
     *
     * @param array<int, int> $eventIds
     * @param array<int, int> $alreadyOpenRequestIds
     */
    public function turnIntoRoomRequests(array $eventIds, array $alreadyOpenRequestIds = []): void
    {
        if ($eventIds === []) {
            return;
        }

        foreach (Event::query()->whereIn('id', $eventIds)->get() as $movedEvent) {
            $movedEvent->update([
                'occupancy_option' => true,
                'declined_room_id' => null,
                'accepted' => false,
            ]);
        }

        $this->roomRequestNotificationService->notifyRoomAdminsOfOpenRequests(
            array_values(array_diff($eventIds, $alreadyOpenRequestIds))
        );
    }
}
