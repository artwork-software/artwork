<?php

use App\Models\Area;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {

    $this->auth_user = User::factory()->create();

    $this->area = Area::factory()->create();

    $this->room = Room::factory()->create();

});

test('users with the permission can create rooms', function() {

    $this->auth_user->givePermissionTo('manage areas');

    $this->actingAs($this->auth_user);

    $this->post('/rooms', [
        'name' => 'TestRoom',
        'description' => 'Test description',
        'user_id' => $this->auth_user->id,
        'area_id' => $this->area->id,
        'temporary' => false
    ]);

    $this->assertDatabaseHas('rooms', [
        'name' => 'TestRoom',
        'area_id' => $this->area->id
    ]);
});

test('users with the permission can update rooms', function() {

    $this->auth_user->givePermissionTo('manage areas');

    $this->actingAs($this->auth_user);

    $this->patch("/rooms/{$this->room->id}", [
        'name' => 'TestRoom',
        'temporary' => false,
        'room_admins' => null
    ]);

    $this->assertDatabaseHas('rooms', [
        'name' => 'TestRoom'
    ]);
});

test('users with the permission can duplicate rooms', function() {

    $old_room = Room::factory()->create([
        'name' => 'TestRoom',
        'description' => 'This is a description',
        'temporary' => false,
        'area_id' => $this->area->id,
    ]);

    $this->auth_user->givePermissionTo('manage areas');
    $this->actingAs($this->auth_user);

    $this->post("/rooms/{$old_room->id}/duplicate")->assertStatus(302);

    $this->assertDatabaseHas('rooms', [
        'name' => '(Kopie) TestRoom'
    ]);


});

test('users with the permission can delete rooms', function() {

    $this->auth_user->givePermissionTo('manage areas');

    $this->actingAs($this->auth_user);

    $this->delete("/rooms/{$this->room->id}");

    $this->assertDatabaseHas('rooms', [
        'id' => $this->room->id,
        'deleted_at' => Date::now()
    ]);
});



