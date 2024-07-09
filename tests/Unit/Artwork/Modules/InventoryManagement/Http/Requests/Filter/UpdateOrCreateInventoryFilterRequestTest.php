<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Filter;

use Artwork\Modules\InventoryManagement\Http\Requests\Filter\UpdateOrCreateInventoryFilterRequest;
use PHPUnit\Framework\TestCase;

class UpdateOrCreateInventoryFilterRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            [
                'filter' => 'array',
                'filter.*.craftId' => 'exists:crafts,id'
            ],
            (new UpdateOrCreateInventoryFilterRequest())->rules()
        );
    }
}
