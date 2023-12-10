<?php

namespace Tests\Feature\EventController;

use App\Models\Event;
use App\Models\EventType;
use App\Models\User;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Room;
use Tests\TestCase;

class EventStoreTest extends TestCase
{

    use WithFaker;

    public function testEventStoreValidation(): void
    {
        // assert unauthenticated
        $this->postJson(route('events.store'))->assertUnauthorized();

        $this->actingAs($this->adminUser());

        $events = Event::all();

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
            ->assertJsonValidationErrors([
                'projectIdMandatory',
                'projectName',
                'eventNameMandatory'
            ]);

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

        $this->assertDatabaseCount('events', $events->count());

        // successful
        $this->postJson(route('events.store'), [
            'title' => 'Test Titel',
            'start' => Carbon::now(),
            'end' => Carbon::now()->addHours(),
            'roomId' => $room->id,
            'projectId' => $project->id,
            'eventTypeId' => $type->id,
            'eventNameMandatory' => false,
            'audience' => 0,
            'is_series' => 0,
            'isLoud' => 0,
            'allDay' => false,
            'isOption' => false,
            'creatingProject' => false,
            'projectIdMandatory' => false,
            'projectName' => $this->faker->company()
        ])
            ->assertSuccessful();

        $this->assertDatabaseCount('events', $events->count() + 1);
    }

    public function testEventStoreCreatesProject()
    {
        $events = Event::all();
        $projects = Project::all();
        $this->assertDatabaseCount('events', $events->count());
        $this->assertDatabaseCount('projects', $projects->count());

        $room = Room::factory()->create();
        $type = EventType::factory()->create();

        $this->actingAs($this->adminUser())
            ->postJson(route('events.store'), [
                'title' => 'Test Titel',
                'start' => Carbon::now(),
                'end' => Carbon::now()->addHours(),
                'roomId' => $room->id,
                'eventTypeId' => $type->id,
                'eventNameMandatory' => false,
                'audience' => 0,
                'is_series' => 0,
                'isLoud' => 0,
                'allDay' => false,
                'isOption' => false,
                'creatingProject' => true,
                'projectIdMandatory' => false,
                'projectName' => $this->faker->company()
            ])
            ->assertSuccessful();

        $this->assertDatabaseCount('events', $events->count() + 1);
        $this->assertDatabaseCount('projects', $projects->count() + 1);
    }

    public function testEventStoreMultiDayEvent()
    {
        $events = Event::all();
        $projects = Project::all();
        $this->assertDatabaseCount('events', $events->count());

        $room = Room::factory()->create();
        $type = EventType::factory()->create();


        $this->actingAs($this->adminUser())
            ->postJson(route('events.store'), [
                'title' => 'Test Titel',
                'start' => Carbon::now(),
                'end' => Carbon::now()->addDay()->addHours(),
                'roomId' => $room->id,
                'eventTypeId' => $type->id,
                'eventNameMandatory' => false,
                'audience' => 0,
                'is_series' => 0,
                'isLoud' => 0,
                'allDay' => false,
                'isOption' => false,
                'creatingProject' => false,
                'projectIdMandatory' => false,
                'projectName' => $this->faker->company()
            ])
            ->assertSuccessful();

        $this->assertDatabaseCount('events', $events->count() + 1);
        /** @var Event $event */
        $event = Event::all()->reverse()->first();
        $this->assertEquals(25, $event->start_time->diffInHours($event->end_time));
        $this->assertDatabaseCount('projects', $projects->count() + 1);
    }
}
