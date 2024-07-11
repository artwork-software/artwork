<?php

namespace Tests\Unit\Artwork\Modules\Task\Http\Requests;

use Artwork\Modules\Task\Http\Requests\FilterOwnTasksRequest;
use PHPUnit\Framework\TestCase;

class FilterOwnTasksRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame([
            'filter' => 'nullable|integer',
        ], (new FilterOwnTasksRequest())->rules());
    }
}