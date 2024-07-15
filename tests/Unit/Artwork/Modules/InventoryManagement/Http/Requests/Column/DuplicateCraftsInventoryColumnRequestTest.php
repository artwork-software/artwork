<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Column;

use Artwork\Modules\InventoryManagement\Http\Requests\Column\DuplicateCraftsInventoryColumnRequest;
use PHPUnit\Framework\TestCase;

class DuplicateCraftsInventoryColumnRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            [
                'columnId' => 'required|exists:crafts_inventory_columns,id'
            ],
            (new DuplicateCraftsInventoryColumnRequest())->rules()
        );
    }
}
