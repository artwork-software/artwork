<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Group;

use Artwork\Modules\InventoryManagement\Http\Requests\Group\UpdateCraftInventoryGroupNameRequest;
use PHPUnit\Framework\TestCase;

class UpdateCraftInventoryGroupNameRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            ['name' => 'required|string'],
            (new UpdateCraftInventoryGroupNameRequest())->rules()
        );
    }
}
