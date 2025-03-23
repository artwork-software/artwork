<?php

namespace App\Http\Controllers;

use Artwork\Modules\Event\Events\EventCreated;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\SubEvent\Models\SubEvent;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;

class SubEventsController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly AuthManager $auth
    ) {
    }

    public function store(Request $request): bool
    {
        $subevent = SubEvent::create($request->only([
            'event_id',
            'eventName',
            'description',
            'start_time',
            'end_time',
            'event_type_id',
            'user_id',
            'audience',
            'is_loud',
            'allDay'
        ]));

        // add Properties to SubEvent
        $subevent->eventProperties()->sync($request->get('eventProperties'));


        // Send Notification to Room Admins
        $event = Event::find($request->event_id);
        $room = $event->room()->first();
        $roomAdmins = $room->users()->wherePivot('is_admin', true)->get();
        foreach ($roomAdmins as $roomAdmin) {
            $notificationTitle = __(
                'notification.event.adjoining_is_loud',
                [],
                $roomAdmin->language
            );
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $this->notificationService->setNotificationTo($roomAdmin);
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService->setEventId($event);
            $this->notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_UPSERT_ROOM_REQUEST);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->createNotification();
        }

        broadcast(new EventCreated(
            $event,
            $event->room_id
        ));
        return true;
    }

    public function update(Request $request, SubEvent $subEvents): bool
    {
        $subEvents->update($request->only([
            'eventName',
            'description',
            'start_time',
            'end_time',
            'event_type_id',
            'user_id',
            'audience',
            'is_loud',
            'allDay'
        ]));

        // add Properties to SubEvent
        $subEvents->eventProperties()->sync($request->get('eventProperties'));

        $event = $subEvents->event;

        broadcast(new EventCreated($event, $event->room_id));

        return true;
    }

    public function destroy(SubEvent $subEvents): void
    {
        $event = $subEvents->event;
        broadcast(new EventCreated($event, $event->room_id));

        $subEvents->forceDelete();
    }
}
