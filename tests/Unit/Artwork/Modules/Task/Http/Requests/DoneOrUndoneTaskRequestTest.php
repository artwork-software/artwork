<?php

namespace Tests\Unit\Artwork\Modules\Task\Http\Requests;

use Artwork\Modules\Task\Http\Requests\DoneOrUndoneTaskRequest;
use PHPUnit\Framework\TestCase;

class DoneOrUndoneTaskRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame([
            'done' => 'required|boolean',
        ], (new DoneOrUndoneTaskRequest())->rules());
    }
}
