<?php

namespace Tests\Unit\Modules\Event\Services;

use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\Event\Services\EventPropertyService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EventPropertyServiceTest extends TestCase
{
    private EventPropertyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EventPropertyService::class);
    }

    #[Test]
    public function get_all_returns_collection(): void
    {
        EventProperty::factory()->count(3)->create();

        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
    }

    #[Test]
    public function get_ids_of_all_existing_returns_plucked_ids(): void
    {
        $props = EventProperty::factory()->count(2)->create();

        $ids = $this->service->getIdsOfAllExisting();

        $this->assertEqualsCanonicalizing($props->pluck('id')->all(), $ids->all());
    }

    #[Test]
    public function find_returns_event_property_by_id(): void
    {
        $prop = EventProperty::factory()->create();

        $found = $this->service->find($prop->id);

        $this->assertInstanceOf(EventProperty::class, $found);
        $this->assertSame($prop->id, $found->id);
    }

    #[Test]
    public function find_or_fail_by_id_returns_event_property(): void
    {
        $prop = EventProperty::factory()->create();

        $found = $this->service->findOrFailById($prop->id);

        $this->assertSame($prop->id, $found->id);
    }

    #[Test]
    public function find_or_fail_by_id_throws_for_missing(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->findOrFailById(999999);
    }

    #[Test]
    public function create_persists_event_property(): void
    {
        $prop = $this->service->create(['name' => 'Quiet', 'icon' => 'IconStar']);

        $this->assertInstanceOf(EventProperty::class, $prop);
        $this->assertTrue($prop->exists);
        $this->assertSame('Quiet', $prop->name);
        $this->assertDatabaseHas('event_properties', ['name' => 'Quiet']);
    }

    #[Test]
    public function update_from_request_updates_attributes(): void
    {
        $prop = EventProperty::factory()->create(['name' => 'Old', 'icon' => 'IconA']);
        $request = Request::create('/x', 'POST', ['name' => 'New', 'icon' => 'IconB']);

        $updated = $this->service->updateFromRequest($prop, $request);

        $this->assertSame('New', $updated->name);
        $this->assertSame('IconB', $updated->icon);
        $this->assertDatabaseHas('event_properties', ['id' => $prop->id, 'name' => 'New']);
    }

    #[Test]
    public function create_from_request_creates_event_property(): void
    {
        $request = Request::create('/x', 'POST', ['name' => 'Loud', 'icon' => 'IconBell']);

        $created = $this->service->createFromRequest($request);

        $this->assertTrue($created->exists);
        $this->assertDatabaseHas('event_properties', ['name' => 'Loud']);
    }

    #[Test]
    public function force_delete_removes_event_property(): void
    {
        $prop = EventProperty::factory()->create();

        $this->service->forceDelete($prop);

        $this->assertDatabaseMissing('event_properties', ['id' => $prop->id]);
    }
}
