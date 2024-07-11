<?php

namespace Tests\Unit\Artwork\Modules\InventoryScheduling\Http\Requests;

use Artwork\Modules\InventoryScheduling\Http\Requests\StoreMultipleInventoryItemsInEvent;
use PHPUnit\Framework\TestCase;

class StoreMultipleInventoryItemsInEventTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            [
                'events' => 'required|array|min:1',
                'events.*.id' => 'required|exists:events,id',
                'events.*.items' => 'required|array|min:1',
                'events.*.items.*.id' => 'required|exists:craft_inventory_items,id',
                'events.*.items.*.quantity' => 'nullable|integer',
            ],
            (new StoreMultipleInventoryItemsInEvent())->rules()
        );
    }
}