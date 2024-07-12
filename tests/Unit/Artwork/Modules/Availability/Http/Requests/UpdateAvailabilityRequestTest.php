<?php

namespace Tests\Unit\Artwork\Modules\Availability\Http\Requests;

use Artwork\Modules\Availability\Https\Requests\UpdateAvailabilityRequest;
use PHPUnit\Framework\TestCase;

class UpdateAvailabilityRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            [
                'id' => 'required|integer|exists:availabilities,id',
                'start_time' => 'nullable',
                'end_time' => 'nullable',
                'date' => 'required',
                'full_day' => 'nullable|boolean',
                'comment' => 'nullable|string|max:20',
                'is_series' => 'nullable|boolean',
                'series_repeat' => 'nullable|string',
                'series_repeat_until' => 'nullable|date',
                'type' => 'required|string',
                'type_before_update' => 'required|string',
            ],
            (new UpdateAvailabilityRequest())->rules()
        );
    }
}