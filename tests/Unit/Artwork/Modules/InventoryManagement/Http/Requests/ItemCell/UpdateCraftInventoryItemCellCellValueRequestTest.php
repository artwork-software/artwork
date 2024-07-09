<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\ItemCell;

use Artwork\Modules\InventoryManagement\Http\Requests\ItemCell\UpdateCraftInventoryItemCellCellValueRequest;
use PHPUnit\Framework\TestCase;

class UpdateCraftInventoryItemCellCellValueRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            [
                'cell_value' => 'nullable|string'
            ],
            (new UpdateCraftInventoryItemCellCellValueRequest())->rules()
        );
    }
}
