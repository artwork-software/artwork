<?php

namespace Tests\Unit\Modules\InventoryScheduling\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
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

    #[Test]
    public function single_booking_exceeding_stock_is_marked_overbooked(): void
    {
        $item = $this->makeItemWithBookings([
            ['quantity' => 10, 'start' => '2026-08-10 10:00', 'end' => '2026-08-10 12:00', 'is_all_day' => false],
        ], stock: '5');

        $overbooked = $this->overbookedByBookingId($item);

        $this->assertSame(5, $overbooked[1]);
    }

    #[Test]
    public function non_competing_bookings_within_stock_are_not_overbooked(): void
    {
        $item = $this->makeItemWithBookings([
            ['quantity' => 4, 'start' => '2026-08-10 08:00', 'end' => '2026-08-10 10:00', 'is_all_day' => false],
            ['quantity' => 4, 'start' => '2026-08-10 12:00', 'end' => '2026-08-10 14:00', 'is_all_day' => false],
        ], stock: '5');

        $overbooked = $this->overbookedByBookingId($item);

        $this->assertSame(0, $overbooked[1]);
        $this->assertSame(0, $overbooked[2]);
    }

    #[Test]
    public function overlapping_bookings_exceeding_stock_are_marked_overbooked(): void
    {
        $item = $this->makeItemWithBookings([
            ['quantity' => 4, 'start' => '2026-08-10 08:00', 'end' => '2026-08-10 12:00', 'is_all_day' => false],
            ['quantity' => 4, 'start' => '2026-08-10 10:00', 'end' => '2026-08-10 14:00', 'is_all_day' => false],
        ], stock: '5');

        $overbooked = $this->overbookedByBookingId($item);

        $this->assertSame(0, $overbooked[1]);
        $this->assertSame(3, $overbooked[2]);
    }

    /**
     * @param array<int, array<string, mixed>> $bookings
     */
    private function makeItemWithBookings(array $bookings, string $stock): CraftInventoryItem
    {
        $item = new CraftInventoryItem();
        $item->setRelation('cells', collect([
            new CraftInventoryItemCell(['cell_value' => $stock]),
        ]));

        $item->setRelation('events', collect($bookings)->map(
            static function (array $attributes, int $index): CraftInventoryItemEvent {
                $booking = new CraftInventoryItemEvent($attributes);
                $booking->id = $index + 1;
                $booking->setRelation('user', null);
                $booking->setRelation('event', null);

                return $booking;
            }
        )->values());

        return $item;
    }

    /**
     * @return array<int, int>
     */
    private function overbookedByBookingId(CraftInventoryItem $item): array
    {
        return $this->service->getItemEvents($item)
            ->mapWithKeys(static fn (array $row): array => [$row['id'] => $row['overbooked']])
            ->all();
    }
}
