<?php

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {

    $this->auth_user = User::factory()->create();
    $this->area = Area::factory()->create();
    $this->auth_user->givePermissionTo(\Artwork\Modules\Permission\Enums\PermissionEnum::ROOM_UPDATE->value);
    $this->actingAs($this->auth_user);
    $this->room = Room::factory()->create();

});

test('users with the permission can view all areas', function () {

    $response = $this->get('/areas');
    $response->assertInertia(fn(Assert $page) => $page
        ->component('Areas/AreaManagement')
    //->has('checklist_templates.data', 1)
    );

    $response->assertStatus(200);
});

test('users with the permission can create areas', function () {
    $this->post('/areas', [
        'name' => 'TestArea'
    ]);

    $this->assertDatabaseHas('areas', [
        'name' => 'TestArea'
    ]);
});

test('users with the permission can update areas', function () {

    $response = $this->patch("/areas/{$this->area->id}", [
        'name' => 'TestArea'
    ]);

    $this->assertDatabaseHas('areas', [
        'name' => 'TestArea'
    ]);
});

test('users with the permission can duplicate areas', function () {

    $old_area = Area::factory()->create([
        'name' => 'TestArea',
    ]);

    $old_area->rooms()->save($this->room);

    $this->post("/areas/{$old_area->id}/duplicate")->assertStatus(302);

    $this->assertDatabaseHas('areas', [
        'name' => '(Kopie) TestArea'
    ]);

    $new_area = Area::where('name', '(Kopie) TestArea')->first();

    $this->assertDatabaseHas('rooms', [
        'area_id' => $new_area->id
    ]);


});

test('users with the permission can delete areas', function () {

    $this->delete("/areas/{$this->area->id}");

    $this->assertDatabaseHas('areas', [
        'id' => $this->area->id,
        'deleted_at' => Date::now()
    ]);
});

test('areas can be moved to trash, then be viewed there (in the trash) and also be restored', function () {

    $this->delete("/areas/{$this->area->id}");

    $this->assertSoftDeleted('areas', ['id' => $this->area->id]);

    $this->patch("/areas/{$this->area->id}/restore");

    $this->assertDatabaseHas('areas', [
        'id' => $this->area->id,
    ]);
});



