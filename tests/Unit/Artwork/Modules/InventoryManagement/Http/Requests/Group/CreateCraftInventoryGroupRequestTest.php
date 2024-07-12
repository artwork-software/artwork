<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Group;

use Artwork\Modules\InventoryManagement\Http\Requests\Group\CreateCraftInventoryGroupRequest;
use PHPUnit\Framework\TestCase;

class CreateCraftInventoryGroupRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            [
                'categoryId' => 'required|integer|exists:craft_inventory_categories,id',
                'name' => 'required|string'
            ],
            (new CreateCraftInventoryGroupRequest())->rules()
        );
    }
}
