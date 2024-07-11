<?php

namespace Tests\Unit\Artwork\Modules\Task\Http\Requests;

use Artwork\Modules\Task\Http\Requests\StoreTaskRequest;
use PHPUnit\Framework\TestCase;

class StoreTaskRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'deadline' => 'sometimes',
            'checklist_id' => 'required|integer|exists:checklists,id',
            'users' => 'array|nullable',
            'users.*.id' => 'integer|exists:users,id',
        ], (new StoreTaskRequest())->rules());
    }
}
