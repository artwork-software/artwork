<?php

namespace Tests\Unit\Artwork\Modules\InventoryScheduling\Http\Requests;

use Artwork\Modules\InventoryScheduling\Http\Requests\DropItemOnInventoryRequest;
use PHPUnit\Framework\TestCase;

class DropItemOnInventoryRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            [
                'quantity' => 'required|integer'
            ],
            (new DropItemOnInventoryRequest())->rules()
        );
    }
}