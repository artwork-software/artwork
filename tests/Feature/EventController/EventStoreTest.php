<?php

namespace Tests\Feature\EventController;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Project;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EventStoreTest extends TestCase
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

        // missing Start
        $this->postJson(route('events.store'), [
            'title' => 'Test Titel',
            'end' => Carbon::now()->addHours(),
            'eventTypeId' => $type->id,
            'eventNameMandatory' => false,
            'projectIdMandatory' => false,
            'creatingProject' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start');

        // missing event type
        $this->postJson(route('events.store'), [
            'title' => 'Test Titel',
            'start' => Carbon::now(),
            'end' => Carbon::now()->addHours(),
            'eventNameMandatory' => false,
            'projectIdMandatory' => false,
            'creatingProject' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('eventTypeId');

        // wrong event type
        $this->postJson(route('events.store'), [
            'title' => 'Test Titel',
            'start' => Carbon::now(),
            'end' => Carbon::now()->addHours(),
            'eventTypeId' => -1,
            'eventNameMandatory' => false,
            'projectIdMandatory' => false,
            'creatingProject' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('eventTypeId');

        // start after end
        $this->postJson(route('events.store'), [
            'title' => 'Test Titel',
            'start' => Carbon::now()->addHours(5),
            'end' => Carbon::now()->addHours(),
            'eventTypeId' => $type->id,
            'eventNameMandatory' => false,
            'projectIdMandatory' => false,
            'creatingProject' => false,
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
            'eventTypeId' => $type->id,
            'eventNameMandatory' => false,
            'projectIdMandatory' => false,
            'creatingProject' => false,
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
                'start' => Carbon::now(),
                'end' => Carbon::now()->addHours(),
                'roomId' => $room->id,
                'projectName' => 'A new Project',
                'eventTypeId' => $type->id,
                'eventNameMandatory' => false,
                'projectIdMandatory' => false,
                'creatingProject' => false,
            ])
            ->assertSuccessful();

        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('projects', 1);
    }

    public function testEventStoreMultiDayEvent()
    {
        $this->assertDatabaseCount('events', 0);

        $room = Room::factory()->create();
        $type = EventType::factory()->create();

        $this->actingAs($this->adminUser())
            ->postJson(route('events.store'), [
                'start' => Carbon::now(),
                'end' => Carbon::now()->addDay()->addHours(),
                'roomId' => $room->id,
                'projectName' => 'A new Project',
                'eventTypeId' => $type->id,
                'eventNameMandatory' => false,
                'projectIdMandatory' => false,
                'creatingProject' => false,

            ])
            ->assertSuccessful();

        $this->assertDatabaseCount('events', 1);
        /** @var Event $event */
        $event = Event::first();
        $this->assertEquals(25, $event->start_time->diffInHours($event->end_time));
        $this->assertDatabaseCount('projects', 1);
    }
// Todo ask if the projects needs auth anyway
//
//    public function testEventStorePermissions()
//    {
//        // user without permissions
//        $user = User::factory()->create();
//        $room = Room::factory()->create();
//        $type = EventType::factory()->create();
//
//        $this->actingAs($user)
//            ->postJson(route('events.store'), [
//                'start' => Carbon::now(),
//                'end' => Carbon::now()->addHours(),
//                'roomId' => $room->id,
//                'projectName' => 'A new Project',
//                'eventTypeId' => $type->id,
//                'eventNameMandatory' => false,
//                'projectIdMandatory' => false,
//                'creatingProject' => false,
//
//            ])
//            ->assertForbidden();
//
//        $this->assertDatabaseCount('events', 0);
//    }
}
