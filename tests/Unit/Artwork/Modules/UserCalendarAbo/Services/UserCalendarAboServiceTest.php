<?php

namespace Tests\Unit\Artwork\Modules\UserCalendarAbo\Services;

use Artwork\Modules\UserCalendarAbo\Models\UserCalenderAbo;
use Artwork\Modules\UserCalendarAbo\Services\UserCalendarAboService;
use Carbon\Carbon;
use Tests\TestCase;

class UserCalendarAboServiceTest extends TestCase
{
    private UserCalendarAboService $userCalendarAboService;
    protected function setUp(): void
    {
        parent::setUp();
        $this->userCalendarAboService = $this->app->make(UserCalendarAboService::class);
    }

    public function testCreate(): void
    {
        $data = [
            'date_range' => true,
            'start_date' => Carbon::now()->subMonth(),
            'end_date' => Carbon::now()->addMonths(4),
            'specific_event_types' => true,
            'event_types' => [1, 2, 3],
            'specific_rooms' => true,
            'selected_rooms' => [1, 2, 3],
            'specific_areas' => true,
            'selected_areas' => [1, 2, 3],
            'enable_notification' => true,
            'notification_time' => 5,
            'notification_time_unit' => 'minutes',
        ];
        $this->userCalendarAboService->create($data);
        $this->assertDatabaseHas('user_calender_abos', $data);
    }

    public function testUpdate(): void
    {
        $calendarAbo = UserCalenderAbo::factory()->create();
        $data = [
            'date_range' => true,
            'start_date' => Carbon::now()->subMonth(),
            'end_date' => Carbon::now()->addMonths(4),
            'specific_event_types' => true,
            'event_types' => [1, 2, 3],
            'specific_rooms' => true,
            'selected_rooms' => [1, 2, 3],
            'specific_areas' => true,
            'selected_areas' => [1, 2, 3],
            'enable_notification' => true,
            'notification_time' => 5,
            'notification_time_unit' => 'minutes',
        ];
        $this->userCalendarAboService->updateByRequest($data, $calendarAbo);
        $this->assertDatabaseHas('user_calender_abos', $data);
    }
}
