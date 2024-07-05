<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Column;

use Artwork\Modules\InventoryManagement\Http\Requests\Category\UpdateCraftInventoryCategoryNameRequest;
use PHPUnit\Framework\TestCase;

class UpdateCraftsInventoryColumnNameRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            ['name' => 'required|string'],
            (new UpdateCraftInventoryCategoryNameRequest())->rules()
        );
    }
}
