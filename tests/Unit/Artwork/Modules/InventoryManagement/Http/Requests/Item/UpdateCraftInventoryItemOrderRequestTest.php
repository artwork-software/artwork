<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Item;

use Artwork\Modules\InventoryManagement\Http\Requests\Group\UpdateCraftInventoryGroupOrderRequest;
use PHPUnit\Framework\TestCase;

class UpdateCraftInventoryItemOrderRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            ['order' => 'required|integer'],
            (new UpdateCraftInventoryGroupOrderRequest())->rules()
        );
    }
}
