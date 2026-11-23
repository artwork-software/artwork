<?php

namespace Artwork\Modules\Event\Observers;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\RoomRequestInboxNotifier;

/**
 * Hält die Seite "Eingehende Anfragen" aktuell: jede Änderung an einer offenen Raumbelegungsanfrage
 * löst einen Hinweis aus. Antworten per Query Builder (EventController::acceptEvent/declineEvent)
 * melden sich dort selbst.
 */
class RoomRequestInboxObserver
{
    /** Felder, die auf der Karte einer Anfrage stehen oder über ihre Sichtbarkeit entscheiden */
    private const array RELEVANT_FIELDS = [
        'occupancy_option',
        'room_id',
        'is_planning',
        'start_time',
        'end_time',
        'event_type_id',
        'eventName',
        'project_id',
    ];

    public function __construct(private readonly RoomRequestInboxNotifier $notifier)
    {
    }

    public function created(Event $event): void
    {
        if ($event->occupancy_option) {
            $this->notifier->notify();
        }
    }

    public function updated(Event $event): void
    {
        $wasOrIsRequest = $event->occupancy_option || $event->getOriginal('occupancy_option');
        if ($wasOrIsRequest && $event->wasChanged(self::RELEVANT_FIELDS)) {
            $this->notifier->notify();
        }
    }

    public function deleted(Event $event): void
    {
        if ($event->occupancy_option) {
            $this->notifier->notify();
        }
    }

    public function restored(Event $event): void
    {
        if ($event->occupancy_option) {
            $this->notifier->notify();
        }
    }
}
