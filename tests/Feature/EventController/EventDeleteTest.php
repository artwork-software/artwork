<?php

namespace Tests\Feature\EventController;

use App\Models\Event;
use App\Models\User;
use Tests\TestCase;

class EventDeleteTest extends TestCase
{

    public function testEventDelete(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->adminUser());
        $this->delete(route('events.delete', ['event' => $event]))
            ->assertSuccessful();

        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }

    public function testEventDeletePermissions(): void
    {
        // user without permissions
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($user);
        $this->delete(route('events.delete', ['event' => $event]))
            ->assertForbidden();

        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }
}
