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
                'events.*.name' => 'string',
                'events.*.start_time' => 'nullable|date',
                'events.*.end_time' => 'nullable|date',
                'events.*.room' => 'array',
                'events.*.room.id' => 'integer|exists:rooms,id',
                'events.*.type' => 'array',
                'events.*.type.id' => 'integer|exists:event_types,id',
            ],
            (new EventBulkCreateRequest())->rules()
        );
    }
}