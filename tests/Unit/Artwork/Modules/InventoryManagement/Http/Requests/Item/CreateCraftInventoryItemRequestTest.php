<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Item;

use Artwork\Modules\InventoryManagement\Http\Requests\Item\CreateCraftInventoryItemRequest;
use PHPUnit\Framework\TestCase;

class CreateCraftInventoryItemRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            [
                'groupId' => 'required|integer|exists:craft_inventory_groups,id',
                'order' => 'required|integer',
            ],
            (new CreateCraftInventoryItemRequest())->rules()
        );
    }
}
