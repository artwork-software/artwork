<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Export;

use Artwork\Modules\InventoryManagement\Http\Requests\Export\CreateInventoryManagementExportRequest;
use PHPUnit\Framework\TestCase;

class CreateInventoryManagementExportRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            [
                'data' => 'required|array|min:1',
                'data.*.craftId' => 'required|numeric|exists:crafts,id',
                'data.*.craftName' => 'required|string',
                'data.*.abbreviation' => 'required|string',
                'data.*.filteredInventoryCategories' => 'array',
                'data.*.filteredInventoryCategories.*.groups' => 'array',
                'data.*.filteredInventoryCategories.*.groups.*.items' => 'array',
                'data.*.filteredInventoryCategories.*.groups.*.items.*.cells' => 'array'
            ],
            (new CreateInventoryManagementExportRequest())->rules()
        );
    }
}
