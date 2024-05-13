<?php

namespace Tests\Unit\Artwork\Modules\Availability\Services;

use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Availability\Services\AvailabilitySeriesService;
use Artwork\Modules\Availability\Services\AvailabilityService;
use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Models\Available;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    private AvailabilityService $availabilityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->adminUser());
        $this->availabilityService = app(AvailabilityService::class);
    }

    public static function availableDataProvider(): \Generator
    {
        yield 'User' => [User::class];
        yield 'Freelancer' => [Freelancer::class];
    }

    /** @dataProvider availableDataProvider */
    public function testCreateMethod(string $availableClass): void
    {
        $available = $availableClass::factory()->create();
        $data = new Request([
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
            'date' => '2022-12-31',
            'full_day' => false,
            'comment' => 'Test comment',
            'is_series' => false,
            'series_repeat' => 'weekly',
            'series_repeat_until' => '2023-01-31',
        ]);

        $availability = $this->availabilityService->create(
            available: $available,
            data: $data,
            notificationService: app(NotificationService::class),
            availabilityConflictService: app(AvailabilityConflictService::class),
            availabilitySeriesService: app(AvailabilitySeriesService::class),
            changeService: app(ChangeService::class),
            schedulingService: app(SchedulingService::class)
        );

        $this->assertInstanceOf(Availability::class, $availability);
        $this->assertEquals($data->start_time, $availability->start_time->format('H:i:s'));
        $this->assertEquals($data->end_time, $availability->end_time->format('H:i:s'));
        $this->assertEquals($data->date, $availability->date);
        $this->assertEquals($data->full_day, $availability->full_day);
        $this->assertEquals($data->comment, $availability->comment);
        $this->assertEquals($data->is_series, $availability->is_series);
    }

    public function testUpdateMethod(): void
    {
        $availability = Availability::factory()->create();
        $data = new Request([
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
            'date' => '2022-12-31',
            'full_day' => false,
            'comment' => 'Test comment',
            'is_series' => false,
        ]);

        $updatedAvailability = $this->availabilityService->update(
            data: $data,
            availability: $availability,
            notificationService: app(NotificationService::class),
            availabilityConflictService: app(AvailabilityConflictService::class)
        );

        $this->assertInstanceOf(Availability::class, $updatedAvailability);
        $this->assertEquals($data->start_time, $updatedAvailability->start_time->format('H:i:s'));
        $this->assertEquals($data->end_time, $updatedAvailability->end_time->format('H:i:s'));
        $this->assertEquals($data->date, $updatedAvailability->date);
        $this->assertEquals($data->full_day, $updatedAvailability->full_day);
        $this->assertEquals($data->comment, $updatedAvailability->comment);
        $this->assertEquals($data->is_series, $updatedAvailability->is_series);
    }

    public function testDeleteMethod(): void
    {
        $availability = Availability::factory()->create();

        $this->availabilityService->delete($availability);

        $this->assertDatabaseMissing('availabilities', ['id' => $availability->id]);
    }
}
