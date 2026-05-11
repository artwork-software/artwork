<?php

namespace Tests\Unit\Modules\EventType\Services;

use Artwork\Modules\EventType\Cache\EventTypeArrayCache;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\EventType\Services\EventTypeService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EventTypeServiceTest extends TestCase
{
    private EventTypeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Reset the static cache to null so the next `getAll`/`getItem` calls hit the repo
        $reflection = new \ReflectionClass(EventTypeArrayCache::class);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);
        $property->setValue(null, null);

        $this->service = app(EventTypeService::class);
    }

    #[Test]
    public function find_by_id_returns_event_type_when_exists(): void
    {
        $eventType = EventType::factory()->create();

        $result = $this->service->findById($eventType->id);

        $this->assertNotNull($result);
        $this->assertSame($eventType->id, $result->id);
    }

    #[Test]
    public function find_by_id_without_cache_returns_null_when_missing(): void
    {
        $result = $this->service->findByIdWithoutCache(999999);

        $this->assertNull($result);
    }

    #[Test]
    public function get_all_returns_collection(): void
    {
        $existing = EventType::count();
        EventType::factory()->count(3)->create();

        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount($existing + 3, $result);
    }

    #[Test]
    public function get_fallback_event_type_creates_one_when_missing(): void
    {
        $this->assertDatabaseMissing('event_types', ['fallback_type' => true]);

        $fallback = $this->service->getFallbackEventType();

        $this->assertNotNull($fallback);
        $this->assertSame('Fallback EventType', $fallback->name);
        $this->assertTrue((bool) $fallback->fallback_type);
        $this->assertDatabaseHas('event_types', ['fallback_type' => true]);
    }

    #[Test]
    public function get_fallback_event_type_returns_existing_when_present(): void
    {
        $existing = EventType::factory()->create([
            'fallback_type' => true,
            'name' => 'Existing Fallback',
        ]);

        $fallback = $this->service->getFallbackEventType();

        $this->assertSame($existing->id, $fallback->id);
    }

    #[Test]
    public function save_persists_event_type(): void
    {
        $eventType = new EventType([
            'name' => 'Test type',
            'hex_code' => '#abcdef',
            'abbreviation' => 'TT',
            'project_mandatory' => false,
            'individual_name' => false,
        ]);

        $saved = $this->service->save($eventType);

        $this->assertTrue($saved->exists);
        $this->assertDatabaseHas('event_types', ['name' => 'Test type']);
    }

    #[Test]
    public function find_by_name_returns_event_type(): void
    {
        $eventType = EventType::factory()->create(['name' => 'Concert']);

        $found = $this->service->findByName('Concert');

        $this->assertNotNull($found);
        $this->assertSame($eventType->id, $found->id);
    }

    #[Test]
    public function find_by_name_returns_null_for_null_input(): void
    {
        $this->assertNull($this->service->findByName(null));
    }

    #[Test]
    public function find_by_name_without_cache_returns_null_for_null(): void
    {
        $this->assertNull($this->service->findByNameWithoutCache(null));
    }
}
