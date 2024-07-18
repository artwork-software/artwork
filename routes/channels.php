<?php

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

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('room.{roomId}.day.{dayString}', function ($user, $roomId, $dayString) {

});