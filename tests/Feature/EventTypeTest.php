<?php

use Artwork\Modules\EventType\Models\EventType;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {

    $this->auth_user = $this->adminUser();
    $this->actingAs($this->auth_user);

    $this->event_type = EventType::factory()->create();
});

test('users with the permission can view all EventTypes', function (): void {

    $response = $this->get('/event_types')
        ->assertInertia(fn(Assert $page) => $page
            ->component('Settings/EventSettings'));

    $response->assertStatus(200);
});

test('users with the permission can create eventTypes', function (): void {

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

test('users with the permission can update eventTypes', function (): void {
    $this->patch("/event_types/{$this->event_type->id}", [
        'name' => 'TestEventType',
        'project_mandatory' => false,
    ]);

    $this->assertDatabaseHas('event_types', [
        'name' => 'TestEventType'
    ]);
});

test('users with the permission can delete eventTypes', function (): void {

    $this->delete("/event_types/{$this->event_type->id}");

    $this->assertDatabaseMissing('event_types', [
        'id' => $this->event_type->id
    ]);
});
