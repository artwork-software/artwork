<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Category;

use Artwork\Modules\InventoryManagement\Http\Requests\Category\UpdateCraftInventoryCategoryNameRequest;
use PHPUnit\Framework\TestCase;

class UpdateCraftInventoryCategoryNameRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            ['name' => 'required|string'],
            (new UpdateCraftInventoryCategoryNameRequest())->rules()
        );
    }
}
