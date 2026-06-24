<?php

namespace Artwork\Modules\Room\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class RoomRequestNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ProjectTabService $projectTabService,
    ) {
    }

    /**
     * Sendet die Raumanfrage-Benachrichtigung an alle Raumadmins des Raums
     * (Fallback: Ersteller des Raums, wenn keine Admins existieren).
     * Bestehende, noch unbeantwortete Anfrage-Benachrichtigungen werden aktualisiert statt dupliziert.
     */
    public function notifyRoomAdmins(Event $event): void
    {
        /** @var Room|null $room */
        $room = $event->room;
        if (!$room) {
            return;
        }

        $admins = $room->users()->wherePivot('is_admin', true)->get();

        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService->setEventId($event->id);
        $this->notificationService->setRoomId($room->id);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_REQUEST);
        $this->notificationService->setButtons(['show_in_calendar', 'accept', 'decline']);

        if ($admins->isNotEmpty()) {
            foreach ($admins as $admin) {
                $this->sendToRecipient($event, $room, $admin);
            }
            return;
        }

        $fallbackUser = User::find($room->user_id);
        if ($fallbackUser === null) {
            return;
        }
        $this->sendToRecipient($event, $room, $fallbackUser);
    }

    private function sendToRecipient(Event $event, Room $room, User $recipient): void
    {
        // notification.event.new_room_request
        $notificationTitle = __('notification.event.new_room_request', [], $recipient->language);
        $broadcastMessage = [
            'id' => Str::uuid()->toString(),
            'type' => 'success',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'link',
                'title' => $room->name,
                'href' => route('rooms.show', $room->id)
            ],
            2 => [
                'type' => 'string',
                'title' => $event->event_type->name . ', ' . $event->eventName,
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

        if (!$this->notificationService->updateExistingRoomRequestNotification(
            $event->id,
            $recipient->id,
            $notificationDescription
        )) {
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationKey(Str::random(15));
            $this->notificationService->setNotificationTo($recipient);
            $this->notificationService->createNotification();
        }
    }
}
