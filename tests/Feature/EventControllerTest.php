<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Project;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testEventStoreValidation()
    {
        // assert unauthenticated
        $this->postJson(route('events.store'))->assertUnauthorized();

        $this->actingAs($this->adminUser());

        $room = Room::factory()->create();
        $project = Project::factory()->create();
        $type = EventType::factory()->create();

        // missing Title
        $this->postJson(route('events.store'), [
            'start' => Carbon::now(),
            'end' => Carbon::now()->addHours(),
            'eventTypeId' => $type->id
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');

        // missing Start
        $this->postJson(route('events.store'), [
            'title' => 'Test Titel',
            'end' => Carbon::now()->addHours(),
            'eventTypeId' => $type->id
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start');

        // missing event type
        $this->postJson(route('events.store'), [
            'title' => 'Test Titel',
            'start' => Carbon::now(),
            'end' => Carbon::now()->addHours(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('eventTypeId');

        // wrong event type
        $this->postJson(route('events.store'), [
            'title' => 'Test Titel',
            'start' => Carbon::now(),
            'end' => Carbon::now()->addHours(),
            'eventTypeId' => -1
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('eventTypeId');

        // start after end
        $this->postJson(route('events.store'), [
            'title' => 'Test Titel',
            'start' => Carbon::now()->addHours(5),
            'end' => Carbon::now()->addHours(),
            'eventTypeId' => $type->id
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('end');

        $this->assertDatabaseCount('events', 0);

        // successful
        $this->postJson(route('events.store'), [
            'title' => 'Test Titel',
            'start' => Carbon::now(),
            'end' => Carbon::now()->addHours(),
            'roomId' => $room->id,
            'projectId' => $project->id,
            'eventTypeId' => $type->id
        ])
            ->assertSuccessful();

        $this->assertDatabaseCount('events', 1);
    }

    public function testEventStoreCreatesProject()
    {
        $this->assertDatabaseCount('events', 0);
        $this->assertDatabaseCount('projects', 0);

        $room = Room::factory()->create();
        $type = EventType::factory()->create();

        $this->actingAs($this->adminUser())
            ->postJson(route('events.store'), [
                'title' => 'Test Titel',
                'start' => Carbon::now(),
                'end' => Carbon::now()->addHours(),
                'roomId' => $room->id,
                'projectName' => 'A new Project',
                'eventTypeId' => $type->id
            ])
            ->assertSuccessful();

        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('projects', 1);
    }

    public function testEventStorePermissions()
    {
        // user without permissions
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $type = EventType::factory()->create();

        $this->actingAs($user)
            ->postJson(route('events.store'), [
                'title' => 'Test Titel',
                'start' => Carbon::now(),
                'end' => Carbon::now()->addHours(),
                'roomId' => $room->id,
                'projectName' => 'A new Project',
                'eventTypeId' => $type->id
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('events', 0);
    }

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

    public function testEventUpdate()
    {
        /** @var Event $event */
        $event = Event::factory()->create();

        $this->actingAs($this->adminUser())
            ->putJson(route('events.update', ['event' => $event->id]), [
                'title' => 'Neuer Test Titel',
                'start' => $event->start_time,
                'end' => $event->end_time,
                'projectId' => $event->project_id,
                'eventTypeId' => $event->event_type_id
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('events', ['name' => 'Neuer Test Titel']);
    }
}
