<?php

use App\Http\Controllers\SchedulingController;
use App\Models\Event;
use App\Models\User;
use App\Notifications\RoomRequestNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

test('notifications that were archived more than 7 days ago are getting deleted', function() {

    $auth_user = User::factory()->create();
    $event = Event::factory()->create();

    $notificationBody = [
        'groupType' => 'EVENTS',
        'type' => 'ROOM_REQUEST',
        'title' => 'New room request',
        'event' => $event,
        'accepted' => true,
        'created_by' => $auth_user
    ];
    Notification::send($auth_user, new RoomRequestNotification($notificationBody));

    $notification = $auth_user->notifications->first();

    $notification->read_at = Carbon::now()->subDays(8);
    $notification->save();

    $taskScheduling = new SchedulingController();
    $taskScheduling->deleteOldNotifications();

    $this->assertDatabaseMissing('notifications', [
        'id' => $notification->id
    ]);

});

test('notifications that were archived less than 7 days ago arent getting deleted', function() {

    $auth_user = User::factory()->create();
    $event = Event::factory()->create();

    $notificationBody = [
        'groupType' => 'EVENTS',
        'type' => 'ROOM_REQUEST',
        'title' => 'New room request',
        'event' => $event,
        'accepted' => true,
        'created_by' => $auth_user,
    ];
    Notification::send($auth_user, new RoomRequestNotification($notificationBody));

    $notification = $auth_user->notifications->first();

    $notification->read_at = Carbon::now()->subDays(5);
    $notification->save();

    $taskScheduling = new SchedulingController();
    $taskScheduling->deleteOldNotifications();

    $this->assertDatabaseHas('notifications', [
        'id' => $notification->id
    ]);

});
