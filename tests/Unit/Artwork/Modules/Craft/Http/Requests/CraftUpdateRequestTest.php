<?php

namespace Tests\Unit\Artwork\Modules\Craft\Http\Requests;

use Artwork\Modules\Craft\Http\Requests\CraftUpdateRequest;
use PHPUnit\Framework\TestCase;

class CraftUpdateRequestTest extends TestCase
{
    public function testRules(): void
    {
        $this->assertSame(
            [
                'name' => 'required|string|min:1|max:255',
                'abbreviation' => 'required|string|min:1|max:3',
                'users' => 'array',
                'assignable_by_all' => 'required|boolean',
            ],
            (new CraftUpdateRequest())->rules()
        );
    }
}
