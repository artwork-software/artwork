<?php

namespace Tests\Unit\Modules\Event\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventCollectionService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EventCollectionServiceTest extends TestCase
{
    private EventCollectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EventCollectionService::class);
    }

    #[Test]
    public function get_events_without_room_returns_eloquent_collection(): void
    {
        $withRoom = Event::factory()->create();
        Event::where('id', $withRoom->id)->update(['room_id' => $withRoom->room_id]);

        $withoutRoom = Event::factory()->create();
        Event::where('id', $withoutRoom->id)->update(['room_id' => null]);

        $events = $this->service->getEventsWithoutRoom();

        $this->assertInstanceOf(Collection::class, $events);
        $this->assertTrue($events->contains('id', $withoutRoom->id));
        $this->assertFalse($events->contains('id', $withRoom->id));
    }

    #[Test]
    public function get_events_without_room_filters_by_project(): void
    {
        $project = Project::factory()->create();

        $matching = Event::factory()->create(['project_id' => $project->id]);
        Event::where('id', $matching->id)->update(['room_id' => null]);

        $other = Event::factory()->create();
        Event::where('id', $other->id)->update(['room_id' => null]);

        $events = $this->service->getEventsWithoutRoom($project);

        $this->assertTrue($events->contains('id', $matching->id));
        $this->assertFalse($events->contains('id', $other->id));
    }

    #[Test]
    public function get_events_without_room_accepts_project_id_as_int(): void
    {
        $project = Project::factory()->create();
        $matching = Event::factory()->create(['project_id' => $project->id]);
        Event::where('id', $matching->id)->update(['room_id' => null]);

        $events = $this->service->getEventsWithoutRoom($project->id);

        $this->assertTrue($events->contains('id', $matching->id));
    }
}
