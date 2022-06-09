<?php

use App\Models\Area;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {

    $this->auth_user = User::factory()->create();

    $this->room = Room::factory()->create();

    $this->event_type = EventType::factory()->create();

    $this->event = Event::factory()->create([
        'room_id' => $this->room->id,
        'start_time' => '2022-05-28T17:00',
        'end_time' => '2022-05-28T18:00'
    ]);

});

test('users with the permission can create events', function() {

    //$this->auth_user->givePermissionTo('manage events');

    $this->actingAs($this->auth_user);

    $res = $this->post('/events', [
        'name' => 'TestEvent',
        'event_type_id' => $this->event_type->id,
        'room_id' => $this->room->id,
        'user_id' => $this->auth_user->id,
        'start_time' => null,
        'end_time' => null,
        'description' => null,
        'occupancy_option' => null,
        'is_loud' => null,
        'audience' => null

    ]);

    $this->assertDatabaseHas('events', [
        'name' => 'TestEvent',
        'event_type_id' => $this->event_type->id,
        'room_id' => $this->room->id,
        'user_id' => $this->auth_user->id,
        'start_time' => null
    ]);
});

test('users with the permission can update events', function() {

    //$this->auth_user->givePermissionTo('manage events');

    $this->actingAs($this->auth_user);

    $this->patch("/events/{$this->event->id}", [
        'name' => 'TestEvent',
    ]);

    $this->assertDatabaseHas('events', [
        'name' => 'TestEvent'
    ]);
});

test('users with the permission can delete events', function() {

    //$this->auth_user->givePermissionTo('manage events');

    $this->actingAs($this->auth_user);

    $this->delete("/events/{$this->event->id}");

    $this->assertDatabaseMissing('events', [
        'id' => $this->event->id,
    ]);
});



