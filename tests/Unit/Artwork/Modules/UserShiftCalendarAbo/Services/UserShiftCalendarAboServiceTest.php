<?php

namespace Tests\Unit\Artwork\Modules\UserShiftCalendarAbo\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserShiftCalendarAbo\Models\UserShiftCalendarAbo;
use Artwork\Modules\UserShiftCalendarAbo\Services\UserShiftCalendarAboService;
use Carbon\Carbon;
use Illuminate\Support\Str;
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
        $user = User::factory()->create();
        $uuid = Str::uuid();
        $data = [
            'calendar_abo_id' => $uuid, // 'calendar_abo_id' => Str::uuid(),
            'date_range' => 1,
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->addMonths(4)->format('Y-m-d'),
            'specific_event_types' => 1,
            'event_types' => "[1, 2, 3]",
            'enable_notification' => 1,
            'notification_time' => 5,
            'notification_time_unit' => 'minutes',
        ];
        $this->userShiftCalendarAboService->create($data, $user->id);
        $this->assertDatabaseHas('user_shift_calendar_abos', $data);
    }

    public function testUpdate(): void
    {
        $calendarAbo = UserShiftCalendarAbo::factory()->create();
        $data = [
            'calendar_abo_id' => Str::uuid(),
            'date_range' => 1,
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->addMonths(4)->format('Y-m-d'),
            'specific_event_types' => 1,
            'event_types' => "[1, 2, 3]",
            'enable_notification' => 1,
            'notification_time' => 5,
            'notification_time_unit' => 'minutes',
        ];
        $this->userShiftCalendarAboService->updateByRequest($calendarAbo, $data);
        $this->assertDatabaseHas('user_shift_calendar_abos', $data);
    }
}
