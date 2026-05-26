<?php

namespace Tests\Unit\Modules\Event\Services;

use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Services\EventStatusService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EventStatusServiceTest extends TestCase
{
    private EventStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EventStatusService::class);
    }

    #[Test]
    public function create_persists_event_status_with_order(): void
    {
        EventStatus::factory()->count(2)->create();

        $status = $this->service->create([
            'name' => 'In Progress',
            'color' => '#ff0000',
            'default' => false,
        ]);

        $this->assertInstanceOf(EventStatus::class, $status);
        $this->assertTrue($status->exists);
        $this->assertSame(3, (int) $status->order);
    }

    #[Test]
    public function create_with_default_true_clears_existing_default(): void
    {
        $existing = EventStatus::factory()->create(['default' => true]);

        $newStatus = $this->service->create([
            'name' => 'Confirmed',
            'color' => '#00ff00',
            'default' => true,
        ]);

        $this->assertFalse((bool) $existing->fresh()->default);
        $this->assertTrue((bool) $newStatus->default);
    }

    #[Test]
    public function update_modifies_event_status(): void
    {
        $status = EventStatus::factory()->create(['name' => 'Old', 'default' => false]);

        $updated = $this->service->update($status, [
            'name' => 'New',
            'color' => '#aabbcc',
            'default' => false,
        ]);

        $this->assertSame('New', $updated->name);
        $this->assertDatabaseHas('event_statuses', ['id' => $status->id, 'name' => 'New']);
    }

    #[Test]
    public function update_with_default_true_clears_other_defaults(): void
    {
        $existing = EventStatus::factory()->create(['default' => true]);
        $target = EventStatus::factory()->create(['default' => false]);

        $this->service->update($target, [
            'name' => $target->name,
            'color' => $target->color,
            'default' => true,
        ]);

        $this->assertFalse((bool) $existing->fresh()->default);
        $this->assertTrue((bool) $target->fresh()->default);
    }

    #[Test]
    public function delete_removes_event_status(): void
    {
        $status = EventStatus::factory()->create();

        $this->service->delete($status);

        $this->assertDatabaseMissing('event_statuses', ['id' => $status->id]);
    }
}
