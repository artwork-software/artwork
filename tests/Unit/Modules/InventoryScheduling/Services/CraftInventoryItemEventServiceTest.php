<?php

namespace Tests\Unit\Modules\InventoryScheduling\Services;

use Artwork\Modules\InventoryScheduling\Models\CraftInventoryItemEvent;
use Artwork\Modules\InventoryScheduling\Services\CraftInventoryItemEventService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CraftInventoryItemEventServiceTest extends TestCase
{
    private CraftInventoryItemEventService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CraftInventoryItemEventService::class);
    }

    #[Test]
    public function create_new_craft_inventory_item_returns_unpersisted_model(): void
    {
        $event = $this->service->createNewCraftInventoryItem([
            'craft_inventory_item_id' => 1,
            'event_id' => 2,
            'quantity' => 3,
        ]);

        $this->assertInstanceOf(CraftInventoryItemEvent::class, $event);
        $this->assertFalse($event->exists);
        $this->assertSame(1, $event->craft_inventory_item_id);
        $this->assertSame(2, $event->event_id);
        $this->assertSame(3, $event->quantity);
    }

    #[Test]
    public function create_new_craft_inventory_item_with_no_attributes_returns_empty_instance(): void
    {
        $event = $this->service->createNewCraftInventoryItem();

        $this->assertInstanceOf(CraftInventoryItemEvent::class, $event);
        $this->assertFalse($event->exists);
    }
}
