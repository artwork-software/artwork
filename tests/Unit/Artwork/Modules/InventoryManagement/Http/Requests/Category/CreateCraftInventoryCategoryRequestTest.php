<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Category;

use Artwork\Modules\InventoryManagement\Http\Requests\Category\CreateCraftInventoryCategoryRequest;
use PHPUnit\Framework\TestCase;

class CreateCraftInventoryCategoryRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            [
                'craftId' => 'required|integer|exists:crafts,id',
                'name' => 'required|string'
            ],
            (new CreateCraftInventoryCategoryRequest())->rules()
        );
    }
}
