<?php

namespace Tests\Unit\Artwork\Modules\Checklist\Http\Requests;

use Artwork\Modules\Checklist\Http\Requests\ChecklistUpdateRequest;
use PHPUnit\Framework\TestCase;

class ChecklistUpdateRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame([
            'user_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'name' => ['sometimes', 'nullable', 'string'],
            'private' => ['boolean'],
            'tasks' => ['sometimes', 'array'],
            'tasks.*.name' => ['sometimes', 'nullable', 'string'],
            'tasks.*.description' => ['sometimes', 'nullable', 'string'],
            'tasks.*.done' => ['required', 'nullable', 'boolean'],
            'tasks.*.order' => ['required', 'nullable', 'int'],

            'assigned_department_ids' => [
                'sometimes',
                'array',
            ],
            'assigned_department_ids.*.*' => ['required', 'exists:departments,id'],
        ], (new ChecklistUpdateRequest())->rules());
    }
}
