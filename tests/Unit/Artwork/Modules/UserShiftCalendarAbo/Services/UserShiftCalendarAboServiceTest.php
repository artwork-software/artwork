<?php

namespace Tests\Unit\Artwork\Modules\UserShiftCalendarAbo\Services;

use Artwork\Modules\UserCalendarAbo\Services\UserCalendarAboService;
use Artwork\Modules\UserShiftCalendarAbo\Models\UserShiftCalendarAbo;
use Artwork\Modules\UserShiftCalendarAbo\Services\UserShiftCalendarAboService;
use Carbon\Carbon;
use Tests\TestCase;

class UserShiftCalendarAboServiceTest extends TestCase
{
    private UserShiftCalendarAboService $userShiftCalendarAboService;
    protected function setUp(): void
    {
        parent::setUp();
        $this->userShiftCalendarAboService = $this->app->make(UserShiftCalendarAboService::class);
    }

    public function testCreate(): void
    {
        $data = [
            'date_range' => true,
            'start_date' => Carbon::now()->subMonth(),
            'end_date' => Carbon::now()->addMonths(4),
            'specific_event_types' => true,
            'event_types' => [1, 2, 3],
            'enable_notification' => true,
            'notification_time' => 5,
            'notification_time_unit' => 'minutes',
        ];
        $this->userShiftCalendarAboService->create($data);
        $this->assertDatabaseHas('user_shift_calender_abos', $data);
    }

    public function testUpdate(): void
    {
        $calendarAbo = UserShiftCalendarAbo::factory()->create();
        $data = [
            'date_range' => true,
            'start_date' => Carbon::now()->subMonth(),
            'end_date' => Carbon::now()->addMonths(4),
            'specific_event_types' => true,
            'event_types' => [1, 2, 3],
            'enable_notification' => true,
            'notification_time' => 5,
            'notification_time_unit' => 'minutes',
        ];
        $this->userShiftCalendarAboService->updateByRequest($calendarAbo, $data);
        $this->assertDatabaseHas('user_shift_calender_abos', $data);
    }

}
