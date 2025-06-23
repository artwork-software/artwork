<?php

namespace Tests\Unit\Artwork\Modules\BudgetManagementCostUnit\Http\Requests;

use Artwork\Modules\Budget\Http\Requests\UpdateBudgetManagementCostUnitRequest;
use PHPUnit\Framework\TestCase;

class UpdateBudgetManagementCostUnitRequestTest extends TestCase
{
    public function testRules(): void
    {
        $this->assertSame(
            [
                'cost_unit_number' => 'string',
                'title' => 'string'
            ],
            (new UpdateBudgetManagementCostUnitRequest())->rules()
        );
    }
}
