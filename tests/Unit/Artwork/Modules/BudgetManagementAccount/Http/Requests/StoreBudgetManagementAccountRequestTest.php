<?php

namespace Tests\Unit\Artwork\Modules\BudgetManagementAccount\Http\Requests;

use Artwork\Modules\Budget\Http\Requests\StoreBudgetManagementAccountRequest;
use PHPUnit\Framework\TestCase;

class StoreBudgetManagementAccountRequestTest extends TestCase
{
    public function testRules(): void
    {
        $this->assertSame(
            [
                'account_number' => 'string',
                'title' => 'string',
                'is_account_for_revenue' => 'boolean'
            ],
            (new StoreBudgetManagementAccountRequest())->rules()
        );
    }
}
