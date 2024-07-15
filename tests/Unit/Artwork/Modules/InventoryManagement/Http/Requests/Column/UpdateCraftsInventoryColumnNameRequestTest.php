<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Column;

use Artwork\Modules\InventoryManagement\Http\Requests\Column\UpdateCraftsInventoryColumnNameRequest;
use PHPUnit\Framework\TestCase;

class UpdateCraftsInventoryColumnNameRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            ['name' => 'string'],
            (new UpdateCraftsInventoryColumnNameRequest())->rules()
        );
    }
}
