<?php

namespace Tests\Unit\Artwork\Modules\EventType\Cache;

use Artwork\Modules\EventType\Cache\EventTypeArrayCache;
use Artwork\Modules\EventType\Models\EventType;
use Tests\TestCase;

class EventTypeArrayCacheTest extends TestCase
{
    public function testGetAll(): void
    {
        $eventTypes = EventType::factory()->count(3)->create();

        EventTypeArrayCache::setAll($eventTypes);

        $this->assertEquals($eventTypes, EventTypeArrayCache::getAll());
    }

    public function testSetAll(): void
    {
        $eventTypes = EventType::factory()->count(3)->create();

        EventTypeArrayCache::setAll($eventTypes);

        $this->assertEquals($eventTypes, EventTypeArrayCache::getAll());
    }

    public function testGetEventType(): void
    {
        $eventType = EventType::factory()->create();

        EventTypeArrayCache::setItem($eventType);

        $this->assertEquals($eventType, EventTypeArrayCache::getItem($eventType->id));
    }

    public function testsetItem(): void
    {
        $eventType = EventType::factory()->create();

        EventTypeArrayCache::setItem($eventType);

        $this->assertEquals($eventType, EventTypeArrayCache::getItem($eventType->id));
    }

    public function testGetEventTypeByName(): void
    {
        $eventType = EventType::factory()->create();

        EventTypeArrayCache::setItem($eventType);

        $this->assertEquals($eventType, EventTypeArrayCache::getItemByName($eventType->name));
    }
}
