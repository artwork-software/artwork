<?php

namespace Tests\Unit\Artwork\Modules\Task\Http\Requests;

use Artwork\Modules\Task\Http\Requests\UpdateTaskOrderInChecklistRequest;
use PHPUnit\Framework\TestCase;

class UpdateTaskOrderInChecklistRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame([
            'checklistTasks' => 'required|array|min:1',
            'checklistTasks.*.id' => 'required|integer|exists:tasks,id',
            'checklistTasks.*.order' => 'required|integer',
        ], (new UpdateTaskOrderInChecklistRequest())->rules());
    }
}