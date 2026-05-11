<?php

namespace Tests\Unit\Pure\Resources\Event;

use Artwork\Modules\Event\Http\Resources\EventIndexResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EventIndexResourceTest extends TestCase
{
    #[Test]
    public function it_transforms_event_into_array(): void
    {
        $eventType = (object) ['id' => 1, 'name' => 'Show'];
        $project = (object) ['id' => 2, 'name' => 'Proj'];
        $model = (object) [
            'name' => 'My Event',
            'event_type' => $eventType,
            'project' => $project,
        ];

        $array = (new EventIndexResource($model))->toArray(new Request());

        $this->assertSame('EventIndexResource', $array['resource']);
        $this->assertSame('My Event', $array['event_name']);
        $this->assertSame($eventType, $array['event_type']);
        $this->assertSame($project, $array['project']);
    }

    #[Test]
    public function wrap_is_null(): void
    {
        $this->assertNull(EventIndexResource::$wrap);
    }
}
