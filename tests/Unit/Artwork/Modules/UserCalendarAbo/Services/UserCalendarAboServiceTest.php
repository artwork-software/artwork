<?php

namespace Tests\Unit\Artwork\Modules\UserCalendarAbo\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCalendarAbo\Models\UserCalendarAbo;
use Artwork\Modules\UserCalendarAbo\Services\UserCalendarAboService;
use Carbon\Carbon;
use Illuminate\Support\Str;
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
        $user = User::factory()->create();
        $uuid = Str::uuid();
        $data = [
            'calendar_abo_id' => $uuid,
            'date_range' => 1,
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->addMonths(4)->format('Y-m-d'),
            'specific_event_types' => 1,
            'event_types' => "[1, 2, 3]",
            'specific_rooms' => 1,
            'selected_rooms' => "[1, 2, 3]",
            'specific_areas' => 1,
            'selected_areas' => "[1, 2, 3]",
            'enable_notification' => 1,
            'notification_time' => 5,
            'notification_time_unit' => 'minutes',
        ];
        $this->userCalendarAboService->create($data, $user->id);
        $this->assertDatabaseHas('user_calendar_abos', $data);
    }

    public function testUpdate(): void
    {
        $calendarAbo = UserCalendarAbo::factory()->create();
        $data = [
            'calendar_abo_id' => Str::uuid(),
            'date_range' => 1,
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->addMonths(4)->format('Y-m-d'),
            'specific_event_types' => 1,
            'event_types' => "[1, 2, 3]",
            'specific_rooms' => 1,
            'selected_rooms' => "[1, 2, 3]",
            'specific_areas' => 1,
            'selected_areas' => "[1, 2, 3]",
            'enable_notification' => 1,
            'notification_time' => 5,
            'notification_time_unit' => 'minutes',
        ];
        $this->userCalendarAboService->updateByRequest($data, $calendarAbo);
        $this->assertDatabaseHas('user_calendar_abos', $data);
    }
}
