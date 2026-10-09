<?php

namespace Artwork\Modules\Room\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventSettingsService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Throwable;

class RoomRequestNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ProjectTabService $projectTabService,
        private readonly EventSettingsService $eventSettingsService,
    ) {
    }

    /**
     * Sendet die Raumanfrage-Benachrichtigung an alle Raumadmins des Raums
     * (Fallback: Ersteller des Raums, wenn keine Admins existieren).
     * Bestehende, noch unbeantwortete Anfrage-Benachrichtigungen werden aktualisiert statt dupliziert.
     */
    public function notifyRoomAdmins(Event $event): void
    {
        // "Termine immer direkt buchbar": es gibt keine Raumanfragen mehr – Sicherheitsnetz für alle Aufrufer
        if ($this->eventSettingsService->alwaysDirectBooking()) {
            return;
        }

        /** @var Room|null $room */
        $room = $event->room;
        if (!$room) {
            return;
        }

        $recipients = $this->recipientsFor($room);
        // Raum der offenen Anfrage gewechselt → Anfrage beim alten Raum zurückziehen
        $this->notificationService->deleteUnhandledRoomRequestNotificationsExcept(
            $event->id,
            $recipients->modelKeys()
        );

        $this->prepareRoomRequest($event, $room);
        $notificationDescription = $this->description($event, $room);
        foreach ($recipients as $recipient) {
            if (
                !$this->notificationService->updateExistingRoomRequestNotification(
                    $event->id,
                    $recipient->id,
                    $notificationDescription
                )
            ) {
                $this->createRoomRequest($recipient, $notificationDescription);
            }
        }
    }

    /**
     * Offene Raumanfragen nach Massenänderungen (Serie, Multi-Edit, Bulk) melden bzw. aktualisieren – wie
     * notifyRoomAdmins() je Termin (bei Raumwechsel bekommen die Admins des neuen Raums die Anfrage, die des alten
     * verlieren sie), aber gebündelt: ein Scan über notifications je 500 Termine statt einem je Termin und
     * Empfänger:in, Raumadmins einmal je Raum. Nur Termine, die noch angefragt, nicht geplant und einem
     * (aktiven) Raum zugeordnet sind.
     *
     * @param array<int, int> $eventIds
     */
    public function notifyRoomAdminsOfOpenRequests(array $eventIds): void
    {
        $this->deliverRoomRequests($eventIds, false);
    }

    /**
     * Wiederhergestellte Termine: die beim Löschen als 'deleted' geschlossene Raumanfrage wieder öffnen – an Ort und
     * Stelle (keine neue Mail/kein Toast, nicht als „geändert“ markiert), damit auch ein Projekt mit vielen
     * Anfragen keine Flut auslöst. Neu gesendet wird nur an Raumadmins ohne passende Meldung (z. B. inzwischen
     * ernannt oder Meldung gelöscht). Gebündelt wie notifyRoomAdminsOfOpenRequests().
     *
     * @param array<int, int> $eventIds
     */
    public function reopenRoomRequestsOfRestoredEvents(array $eventIds): void
    {
        $this->deliverRoomRequests($eventIds, true);
    }

    /**
     * Läuft nach bereits gespeicherten Änderungen (teils im finally): Fehler werden gemeldet, nicht geworfen –
     * sie dürfen den ursprünglichen Fehler nicht überdecken; je Termin abgesichert.
     *
     * @param array<int, int> $eventIds
     */
    private function deliverRoomRequests(array $eventIds, bool $reopenClosedRequests): void
    {
        try {
            if ($this->eventSettingsService->alwaysDirectBooking()) {
                return;
            }

            $events = $this->openRoomRequestEvents($eventIds);
            if ($events->isEmpty()) {
                return;
            }

            $notificationsByEvent = $this->notificationService->roomRequestNotificationsByEvent(
                array_map('intval', $events->modelKeys()),
                $reopenClosedRequests
            );
        } catch (Throwable $exception) {
            report($exception);

            return;
        }

        $recipientsByRoom = [];
        $obsoleteNotificationIds = [];
        $reopenNotificationIds = [];
        $deliveries = [];
        foreach ($events as $event) {
            try {
                /** @var Room $room */
                $room = $event->room;
                $recipients = $recipientsByRoom[$room->id] ??= $this->recipientsFor($room);
                $recipientIds = array_flip($recipients->modelKeys());
                $notifications = $notificationsByEvent[(int) $event->id] ?? [];

                $openByRecipient = [];
                foreach ($notifications as $notification) {
                    if (isset($notification['data']['handledStatus'])) {
                        continue;
                    }
                    // Raum der offenen Anfrage gewechselt → Anfrage beim alten Raum zurückziehen
                    if (!isset($recipientIds[$notification['notifiable_id']])) {
                        $obsoleteNotificationIds[] = $notification['id'];
                        continue;
                    }
                    $openByRecipient[$notification['notifiable_id']] ??= $notification;
                }

                $reopenedRecipientIds = [];
                if ($reopenClosedRequests) {
                    // Jüngste beim Löschen geschlossene Meldung je Empfänger:in (ältere stammen aus früheren Runden)
                    foreach ($notifications as $notification) {
                        $recipientId = $notification['notifiable_id'];
                        if (
                            ($notification['data']['handledStatus'] ?? null) !== 'deleted'
                            || !isset($recipientIds[$recipientId])
                            || isset($openByRecipient[$recipientId])
                        ) {
                            continue;
                        }
                        $reopenNotificationIds[] = $notification['id'];
                        $openByRecipient[$recipientId] = $notification;
                        $reopenedRecipientIds[$recipientId] = true;
                    }
                }

                $deliveries[] = [$event, $room, $recipients, $openByRecipient, $reopenedRecipientIds];
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        try {
            $this->notificationService->deleteRoomRequestNotificationsByIds($obsoleteNotificationIds);
            $this->notificationService->reopenRoomRequestNotificationsByIds($reopenNotificationIds);
        } catch (Throwable $exception) {
            report($exception);
        }

        foreach ($deliveries as [$event, $room, $recipients, $openByRecipient, $reopenedRecipientIds]) {
            try {
                $this->deliverToRecipients($event, $room, $recipients, $openByRecipient, $reopenedRecipientIds);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * Bestehende offene Meldung aktualisieren (Primärschlüssel-Update, kein Scan) – wiedergeöffnete bleiben
     * unverändert –, fehlende neu anlegen.
     *
     * @param Collection<int, User> $recipients
     * @param array<int, array{id: string, notifiable_id: int, data: array<string, mixed>}> $openByRecipient
     * @param array<int, bool> $reopenedRecipientIds
     */
    private function deliverToRecipients(
        Event $event,
        Room $room,
        Collection $recipients,
        array $openByRecipient,
        array $reopenedRecipientIds
    ): void {
        $notificationDescription = null;
        $prepared = false;
        foreach ($recipients as $recipient) {
            $recipientId = (int) $recipient->id;
            if (isset($reopenedRecipientIds[$recipientId])) {
                continue;
            }
            $notificationDescription ??= $this->description($event, $room);

            if (isset($openByRecipient[$recipientId])) {
                $this->notificationService->markRoomRequestNotificationModified(
                    $openByRecipient[$recipientId]['id'],
                    $openByRecipient[$recipientId]['data'],
                    $notificationDescription
                );
                continue;
            }

            if (!$prepared) {
                $this->prepareRoomRequest($event, $room);
                $prepared = true;
            }
            $this->createRoomRequest($recipient, $notificationDescription);
        }
    }

    /**
     * @param array<int, int> $eventIds
     * @return Collection<int, Event>
     */
    private function openRoomRequestEvents(array $eventIds): Collection
    {
        $eventIds = array_values(array_unique(array_map('intval', $eventIds)));
        if ($eventIds === []) {
            return new Collection();
        }

        // whereHas('room'): liegt der Raum inzwischen im Papierkorb, gibt es niemanden, der die Anfrage
        // beantworten könnte – dann auch nichts wieder öffnen
        return Event::query()
            ->with(['room', 'project', 'event_type'])
            ->whereIn('id', $eventIds)
            ->where('occupancy_option', true)
            ->where('is_planning', false)
            ->whereNotNull('room_id')
            ->whereHas('room')
            ->get();
    }

    /**
     * Raumadmins, ersatzweise die Person, die den Raum angelegt hat.
     *
     * @return Collection<int, User>
     */
    private function recipientsFor(Room $room): Collection
    {
        $admins = $room->users()->wherePivot('is_admin', true)->get();

        return $admins->isNotEmpty()
            ? $admins
            : User::query()->whereKey($room->user_id)->get();
    }

    private function prepareRoomRequest(Event $event, Room $room): void
    {
        $this->notificationService->clearNotificationData();
        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService->setEventId($event->id);
        $this->notificationService->setRoomId($room->id);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_REQUEST);
        $this->notificationService->setButtons(['show_in_calendar', 'accept', 'decline']);
    }

    /**
     * @param array<int, array<string, mixed>> $notificationDescription
     */
    private function createRoomRequest(User $recipient, array $notificationDescription): void
    {
        // notification.event.new_room_request
        $notificationTitle = __('notification.event.new_room_request', [], $recipient->language);
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setBroadcastMessage([
            'id' => Str::uuid()->toString(),
            'type' => 'success',
            'message' => $notificationTitle
        ]);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationKey(Str::random(15));
        $this->notificationService->setNotificationTo($recipient);
        $this->notificationService->createNotification();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function description(Event $event, Room $room): array
    {
        return [
            1 => [
                'type' => 'link',
                'title' => $room->name,
                'href' => route('rooms.show', $room->id)
            ],
            2 => [
                'type' => 'string',
                'title' => ($event->event_type?->name ?? '') . ', ' . $event->eventName,
                'href' => null
            ],
            3 => [
                'type' => 'link',
                'title' => $event->project->name ?? '',
                'href' => $event->project ?
                    route(
                        'projects.tab',
                        [
                            $event->project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::CALENDAR
                            )
                        ]
                    ) :
                    null
            ],
            4 => [
                'type' => 'string',
                'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                    Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                'href' => null
            ]
        ];
    }
}
