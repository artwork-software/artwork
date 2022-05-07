<?php

use App\Models\Area;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {

    $this->auth_user = User::factory()->create();

    $this->area = Area::factory()->create();

});

test('users with the permission can view all areas', function() {

    $response = $this->get('/areas')
        ->assertInertia(fn(Assert $page) => $page
            ->component('Areas/AreaManagement')
        //->has('checklist_templates.data', 1)
        );

    $response->assertStatus(200);
});

test('users with the permission can create areas', function() {

    $this->auth_user->givePermissionTo('manage areas');

    $this->actingAs($this->auth_user);

    $this->post('/areas', [
        'name' => 'TestArea'
    ]);

    $this->assertDatabaseHas('areas', [
        'name' => 'TestArea'
    ]);
});

test('users with the permission can update areas', function() {

    $this->auth_user->givePermissionTo('manage areas');

    $this->actingAs($this->auth_user);

    $this->patch("/areas/{$this->area->id}", [
        'name' => 'TestArea'
    ]);

    $this->assertDatabaseHas('areas', [
        'name' => 'TestArea'
    ]);
});

test('users with the permission can delete areas', function() {

    $this->auth_user->givePermissionTo('manage areas');

    $this->actingAs($this->auth_user);

    $this->delete("/areas/{$this->area->id}");

    $this->assertDatabaseHas('areas', [
        'id' => $this->area->id,
        'deleted_at' => Date::now()
    ]);
});

test('areas can be moved to trash, then be viewed there (in the trash) and also be restored', function() {

    $this->auth_user->givePermissionTo('manage areas');
    $this->actingAs($this->auth_user);

    $this->delete("/areas/{$this->area->id}");

    $response = $this->get('/areas/trashed')
        ->assertInertia(fn(Assert $page) => $page
            ->component('Trash/Areas')
        ->has('trashed_areas.data', 1));

    $response->assertStatus(200);

    $this->patch("/areas/{$this->area->id}/restore");

    $this->assertDatabaseHas('areas', [
        'id' => $this->area->id,
    ]);


});



