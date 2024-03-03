<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Models\Event;
use App\Models\SubEvents;
use App\Support\Services\NotificationService;
use Illuminate\Http\Request;

class SubEventsController extends Controller
{
    protected ?NotificationService $notificationService = null;

    protected ?\stdClass $notificationData = null;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
        $this->notificationData = new \stdClass();
        $this->notificationData->event = new \stdClass();
    }

    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
        SubEvents::create($request->only([
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
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_UPSERT_ROOM_REQUEST);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->createNotification();
        }
    }

    public function show(): void
    {
    }

    public function edit(SubEvents $subEvents): void
    {
    }

    public function update(Request $request, SubEvents $subEvents): void
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
    }

    public function destroy(SubEvents $subEvents): void
    {
        $subEvents->forceDelete();
    }
}
