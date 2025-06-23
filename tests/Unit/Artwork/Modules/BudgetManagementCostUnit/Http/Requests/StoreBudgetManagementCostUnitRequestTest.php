<?php

namespace Tests\Unit\Artwork\Modules\BudgetManagementCostUnit\Http\Requests;

use Artwork\Modules\Budget\Http\Requests\StoreBudgetManagementCostUnitRequest;
use PHPUnit\Framework\TestCase;

class StoreBudgetManagementCostUnitRequestTest extends TestCase
{
    public function testRules(): void
    {
        $this->assertSame(
            [
                'cost_unit_number' => 'string',
                'title' => 'string'
            ],
            (new StoreBudgetManagementCostUnitRequest())->rules()
        );
    }
}
