<?php

namespace Tests\Unit\Artwork\Modules\EventType\Cache;

use Artwork\Modules\EventType\Cache\EventTypeMemoryCache;
use Artwork\Modules\EventType\Models\EventType;
use Tests\TestCase;

class EventTypeMemoryCacheTest extends TestCase
{
    public function testGetAll(): void
    {
        $eventTypes = EventType::factory()->count(3)->create();

        EventTypeMemoryCache::setAll($eventTypes);

        $this->assertEquals($eventTypes, EventTypeMemoryCache::getAll());
    }

    public function testSetAll(): void
    {
        $eventTypes = EventType::factory()->count(3)->create();

        EventTypeMemoryCache::setAll($eventTypes);

        $this->assertEquals($eventTypes, EventTypeMemoryCache::getAll());
    }

    public function testGetEventType(): void
    {
        $eventType = EventType::factory()->create();

        EventTypeMemoryCache::setEventType($eventType);

        $this->assertEquals($eventType, EventTypeMemoryCache::getEventType($eventType->id));
    }

    public function testSetEventType(): void
    {
        $eventType = EventType::factory()->create();

        EventTypeMemoryCache::setEventType($eventType);

        $this->assertEquals($eventType, EventTypeMemoryCache::getEventType($eventType->id));
    }

    public function testGetEventTypeByName(): void
    {
        $eventType = EventType::factory()->create();

        EventTypeMemoryCache::setEventType($eventType);

        $this->assertEquals($eventType, EventTypeMemoryCache::getEventTypeByName($eventType->name));
    }
}
