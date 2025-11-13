<?php

namespace Tests\Unit\Artwork\Modules\Event\Http\Requests;

use Artwork\Modules\Event\Http\Requests\EventBulkCreateRequest;
use PHPUnit\Framework\TestCase;

class EventBulkCreateRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            [
                'events' => 'required|array',
                'events.*.name' => 'nullable|string',
                'events.*.start_time' => 'nullable',
                'events.*.end_time' => 'nullable',
                'events.*.end_day' => 'nullable',
                'events.*.room' => 'array',
                'events.*.room.id' => 'integer|exists:rooms,id',
                'events.*.type' => 'array',
                'events.*.type.id' => 'integer|exists:event_types,id',
            ],
            (new EventBulkCreateRequest())->rules()
        );
    }
}
