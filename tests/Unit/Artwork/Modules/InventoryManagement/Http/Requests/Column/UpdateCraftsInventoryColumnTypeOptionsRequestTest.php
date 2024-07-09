<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Column;

use Artwork\Modules\InventoryManagement\Http\Requests\Column\UpdateCraftsInventoryColumnTypeOptionsRequest;
use PHPUnit\Framework\TestCase;

class UpdateCraftsInventoryColumnTypeOptionsRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            [
                'selectOptions' => 'required|array|min:1',
                'selectOptions.*' => 'required|string'
            ],
            (new UpdateCraftsInventoryColumnTypeOptionsRequest())->rules()
        );
    }
}
