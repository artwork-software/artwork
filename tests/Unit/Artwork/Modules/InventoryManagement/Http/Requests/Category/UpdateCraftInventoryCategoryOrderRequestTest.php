<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Category;

use Artwork\Modules\InventoryManagement\Http\Requests\Category\UpdateCraftInventoryCategoryOrderRequest;
use PHPUnit\Framework\TestCase;

class UpdateCraftInventoryCategoryOrderRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            ['order' => 'required|integer'],
            (new UpdateCraftInventoryCategoryOrderRequest())->rules()
        );
    }
}
