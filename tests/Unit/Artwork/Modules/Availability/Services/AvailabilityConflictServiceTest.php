<?php

namespace Tests\Unit\Artwork\Modules\Availability\Services;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AvailabilityConflictServiceTest extends TestCase
{
    private AvailabilityConflictService $availabilityConflictService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availabilityConflictService = app(AvailabilityConflictService::class);
    }

    public function testCreateMethod(): void
    {
        $availability = Availability::factory()->create();
        $shift = Shift::factory()->create();
        $data = [
            'availability_id' => $availability->id,
            'shift_id' => $shift->id,
            'user_name' => 'Test User',
            'date' => '2022-12-31',
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
        ];

        $this->availabilityConflictService->create($data);

        $this->assertDatabaseHas('availabilities_conflicts', $data);
    }

    public function testCheckAvailabilityConflictsOnDayMethod(): void
    {
        $day = '2022-12-31';
        $notificationService = app(NotificationService::class);
        Event::fake();
        $this->availabilityConflictService->checkAvailabilityConflictsOnDay($day, $notificationService);
        Event::assertNothingDispatched();
    }

    public function testCheckAvailabilityConflictsShiftsMethod(): void
    {
        $shift = Shift::factory()->create();
        $notificationService = app(NotificationService::class);
        Event::fake();
        $this->availabilityConflictService->checkAvailabilityConflictsShifts($shift, $notificationService, $this->adminUser());
        Event::assertNotDispatched(NotificationEnum::NOTIFICATION_CONFLICT->value);
    }
}
