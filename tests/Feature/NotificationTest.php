<?php

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Notifications\RoomRequestNotification;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

test('notifications that were archived more than 7 days ago are getting deleted', function (): void {

    $auth_user = User::factory()->create();
    $event = Event::factory()->create();

    $notificationBody = new \stdClass();
    $notificationBody->groupType = 'EVENTS';
    $notificationBody->type = 'ROOM_REQUEST';
    $notificationBody->title = 'new room request';
    $notificationBody->event = $event;
    $notificationBody->accepted = true;
    $notificationBody->created_by = $auth_user;
    Notification::send($auth_user, new RoomRequestNotification($notificationBody));

    $notification = $auth_user->notifications->first();

    $notification->read_at = Carbon::now()->subDays(8);
    $notification->save();

    $taskScheduling = app()->get(SchedulingService::class);
    $taskScheduling->deleteOldNotifications();

    $this->assertDatabaseMissing('notifications', [
        'id' => $notification->id
    ]);
});

test('notifications that were archived less than 7 days ago arent getting deleted', function (): void {

    $auth_user = User::factory()->create();
    $event = Event::factory()->create();

    $notificationBody = new \stdClass();
    $notificationBody->groupType = 'EVENTS';
    $notificationBody->type = 'ROOM_REQUEST';
    $notificationBody->title = 'new room request';
    $notificationBody->event = $event;
    $notificationBody->accepted = true;
    $notificationBody->created_by = $auth_user;
    Notification::send($auth_user, new RoomRequestNotification($notificationBody));

    $notification = $auth_user->notifications->first();

    $notification->read_at = Carbon::now()->subDays(5);
    $notification->save();

    $taskScheduling = app()->get(SchedulingService::class);
    $taskScheduling->deleteOldNotifications();

    $this->assertDatabaseHas('notifications', [
        'id' => $notification->id
    ]);
});
