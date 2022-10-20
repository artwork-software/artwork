<?php

namespace Tests\Feature\EventController;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventUpdateTest extends TestCase
{
    use RefreshDatabase;

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
                'eventTypeId' => $event->event_type_id,
                'eventNameMandatory' => false,
                'projectIdMandatory' => false,
                'creatingProject' => false,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('events', ['name' => 'Neuer Test Titel']);
    }
}
