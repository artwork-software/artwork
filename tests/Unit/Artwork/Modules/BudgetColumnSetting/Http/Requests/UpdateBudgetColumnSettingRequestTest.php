<?php

namespace Tests\Unit\Artwork\Modules\BudgetColumnSetting\Http\Requests;

use Artwork\Modules\Budget\Http\Requests\UpdateBudgetColumnSettingRequest;
use PHPUnit\Framework\TestCase;

class UpdateBudgetColumnSettingRequestTest extends TestCase
{
    public function testRules(): void
    {
        $this->assertSame(
            [
                'column_name' => 'string'
            ],
            (new UpdateBudgetColumnSettingRequest())->rules()
        );
    }
}
