<?php

namespace Tests\Unit\Artwork\Modules\Task\Http\Requests;

use Artwork\Modules\Task\Http\Requests\UpdateTaskRequest;
use PHPUnit\Framework\TestCase;

class UpdateTaskRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame([
            'id' => 'required|exists:tasks,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'deadlineDate' => 'required',
            'checklist_id' => 'required|integer|exists:checklists,id',
            'users' => 'array|nullable',
            'users.*.id' => 'integer|exists:users,id',
        ], (new UpdateTaskRequest())->rules());
    }
}
