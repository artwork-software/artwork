<?php

use App\Models\EventType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {

    $this->auth_user = $this->adminUser();
    $this->actingAs($this->auth_user);

    $this->event_type = EventType::factory()->create();

});

test('users with the permission can view all EventTypes', function() {

    $response = $this->get('/event_types')
        ->assertInertia(fn(Assert $page) => $page
            ->component('Settings/EventSettings')
        );

    $response->assertStatus(200);
});

test('users with the permission can create eventTypes', function() {

    $this->post('/event_types', [
        'name' => 'TestEventType',
        'abbreviation' => 'lel',
        'project_mandatory' => false,
        'individual_name' => false,
        'hex_code' => '#000000'
    ]);

    $this->assertDatabaseHas('event_types', [
        'name' => 'TestEventType'
    ]);
});

test('users with the permission can update eventTypes', function() {

    $this->patch("/event_types/{$this->event_type->id}", [
        'name' => 'TestEventType'
    ]);

    $this->assertDatabaseHas('event_types', [
        'name' => 'TestEventType'
    ]);
});

test('users with the permission can delete eventTypes', function() {

    $this->delete("/event_types/{$this->event_type->id}");

    $this->assertDatabaseMissing('event_types', [
        'id' => $this->event_type->id
    ]);
});



