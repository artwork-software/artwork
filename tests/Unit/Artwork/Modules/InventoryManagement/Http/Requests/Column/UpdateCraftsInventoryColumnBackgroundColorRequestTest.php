<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Column;

use Artwork\Modules\InventoryManagement\Http\Requests\Column\UpdateCraftsInventoryColumnBackgroundColorRequest;
use PHPUnit\Framework\TestCase;

class UpdateCraftsInventoryColumnBackgroundColorRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            ['background_color' => 'string'],
            (new UpdateCraftsInventoryColumnBackgroundColorRequest())->rules()
        );
    }
}
