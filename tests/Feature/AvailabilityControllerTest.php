<?php

namespace Tests\Feature;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Tests\TestCase;

class AvailabilityControllerTest extends TestCase
{
    public function testUpdateMethod(): void
    {
        $availability = Availability::factory()->create();
        $availabilityConflictService = $this->createMock(AvailabilityConflictService::class);

        $data = [
            'id' => $availability->id,
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
            'date' => '2022-12-31',
            'full_day' => false,
            'comment' => 'Test',
            'is_series' => false,
            'series_repeat' => 'weekly',
            'series_repeat_until' => '2023-01-31',
            'type' => 'availability',
            'type_before_update' => 'availability',
        ];

        $response = $this->patch(route('update.availability', [
            'availability' => $availability->id,
            'availabilityConflictService' => $availabilityConflictService,
        ]), $data);

        $response->assertStatus(302);
    }
}
