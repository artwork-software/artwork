<?php

namespace Tests\Feature\EventController;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function testEventDelete()
    {
        $event = Event::factory()->create();

        $this->actingAs($this->adminUser())
            ->delete(route('events.delete', ['event' => $event]))
            ->assertSuccessful();

        $this->assertDatabaseCount('events', 0);
    }

    public function testEventDeletePermissions()
    {
        // user without permissions
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($user)
            ->delete(route('events.delete', ['event' => $event]))
            ->assertForbidden();

        $this->assertDatabaseCount('events', 1);
    }
}
