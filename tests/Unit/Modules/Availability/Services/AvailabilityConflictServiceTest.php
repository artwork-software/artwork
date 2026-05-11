<?php

namespace Tests\Unit\Modules\Availability\Services;

use Artwork\Modules\Availability\Models\AvailabilitiesConflict;
use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AvailabilityConflictServiceTest extends TestCase
{
    private AvailabilityConflictService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AvailabilityConflictService::class);
    }

    #[Test]
    public function create_persists_availability_conflict(): void
    {
        $availability = Availability::factory()->create([
            'available_type' => User::class,
            'available_id' => User::factory(),
        ]);

        $shift = Shift::factory()->create();
        $this->service->create([
            'availability_id' => $availability->id,
            'shift_id' => $shift->id,
            'user_name' => 'Tester',
            'date' => '2025-03-01',
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $this->assertDatabaseHas('availabilities_conflicts', [
            'availability_id' => $availability->id,
            'user_name' => 'Tester',
        ]);
    }

    #[Test]
    public function check_availability_conflicts_on_day_returns_void_when_no_user(): void
    {
        $this->service->checkAvailabilityConflictsOnDay(
            '2025-01-01',
            app(NotificationService::class),
            null,
            null
        );

        $this->assertTrue(true);
    }
}
