<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Group;

use Artwork\Modules\InventoryManagement\Http\Requests\Group\UpdateCraftInventoryGroupOrderRequest;
use PHPUnit\Framework\TestCase;

class UpdateCraftInventoryGroupOrderRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            ['order' => 'required|integer'],
            (new UpdateCraftInventoryGroupOrderRequest())->rules()
        );
    }
}
