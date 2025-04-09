<?php

use Artwork\Modules\Chat\Models\Chat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('events', function () {
    return Auth::check();
});

Broadcast::channel('users', function () {
    return Auth::check();
});

Broadcast::channel('projects', function () {
    return Auth::check();
});

Broadcast::channel('departments', function () {
    return Auth::check();
});

Broadcast::channel('shifts', function () {
    return Auth::check();
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('room.{roomId}.day.{dayString}', function ($user, $roomId, $dayString): void {
});


Broadcast::channel('shift-plan.room.{roomId}', function ($roomId) {
    return Auth::check();
});

Broadcast::channel('destroy.events.room.{roomId}', function ($roomId) {
    return Auth::check();
});

Broadcast::channel('shift-plan.shift.{shiftId}', function ($shiftId) {
    return Auth::check();
});

Broadcast::channel('shift-plan.multi-shifts', function () {
    return Auth::check();
});

Broadcast::channel('event.room.{roomId}', function ($roomId) {
    return Auth::check();
});

Broadcast::channel('project.{projectId}', function ($projectId) {
    return Auth::check();
});

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return Chat::where('id', $chatId)
        ->whereHas('users', fn($q) => $q->where('users.id', $user->id))
        ->exists();
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('event-verification-index.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});