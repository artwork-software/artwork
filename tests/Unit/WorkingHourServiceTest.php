<?php

namespace Tests\Unit;

use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\User\Services\WorkingHourService;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkingHourServiceTest extends TestCase
{
    use DatabaseTransactions;

    private WorkingHourService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WorkingHourService::class);
    }

    public function test_calculate_weekly_working_hours_includes_shifts_and_individual_times(): void
    {
        $user = User::factory()->create(['weekly_working_hours' => 40]);

        // Tagessoll: 40/7 = 5.71h (ca 343 min)
        // Montag, 2026-01-19
        $monday = Carbon::parse('2026-01-19');

        // Schicht: 08:00 - 12:00 (4h = 240 min)
        $qualification = ShiftQualification::create([
            'name' => 'Test Qualification',
            'icon' => 'test',
            'available' => true
        ]);
        $shift = Shift::factory()->create(['break_minutes' => 0]);
        $user->shifts()->attach($shift, [
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'shift_qualification_id' => $qualification->id,
        ]);

        // Individuelle Zeit: 1h = 60 min
        IndividualTime::create([
            'timeable_id' => $user->id,
            'timeable_type' => User::class,
            'working_time_minutes' => 60,
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->toDateString(),
        ]);

        $startDate = $monday->copy()->startOfWeek();
        $endDate = $monday->copy()->endOfWeek();

        $result = $this->service->calculateWeeklyWorkingHours($user, $startDate, $endDate);

        $weekNum = ltrim($monday->format('W'), '0');
        $this->assertArrayHasKey($weekNum, $result);

        // Geplant sollte sein: 240 (Schicht) + 60 (individuell) = 300 min = 5:00h
        $this->assertEquals('5h 0m', $result[$weekNum]['planned']);
    }

    public function test_off_work_day_sets_daily_target_to_zero(): void
    {
        $user = User::factory()->create(['weekly_working_hours' => 40]);
        $monday = Carbon::parse('2026-01-19');

        // Arbeitsfreier Tag am Montag
        Vacation::create([
            'vacationer_id' => $user->id,
            'vacationer_type' => User::class,
            'date' => $monday->toDateString(),
            'type' => 'OFF_WORK',
            'full_day' => true,
        ]);

        $startDate = $monday->copy()->startOfWeek();
        $endDate = $monday->copy()->endOfWeek();

        $result = $this->service->calculateWeeklyWorkingHours($user, $startDate, $endDate);

        $weekNum = ltrim($monday->format('W'), '0');

        $this->assertEquals('34h 18m', $result[$weekNum]['daily_target']);
    }

    public function test_precompute_weekly_working_hours_consistent_with_calculate(): void
    {
        $user = User::factory()->create([
            'weekly_working_hours' => 40,
            'can_work_shifts' => true
        ]);
        $monday = Carbon::parse('2026-01-19');
        $startDate = $monday->copy()->startOfWeek();
        $endDate = $monday->copy()->endOfWeek();

        // 1. Schicht
        $qualification = ShiftQualification::create(['name' => 'Test', 'icon' => 'test', 'available' => true]);
        $shift = Shift::factory()->create(['break_minutes' => 0]);
        $user->shifts()->attach($shift, [
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00', // 2h
            'shift_qualification_id' => $qualification->id,
        ]);

        // 2. Individuelle Zeit
        IndividualTime::create([
            'timeable_id' => $user->id,
            'timeable_type' => User::class,
            'working_time_minutes' => 30, // 0.5h
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->toDateString(),
        ]);

        // 3. OFF_WORK am Dienstag
        Vacation::create([
            'vacationer_id' => $user->id,
            'vacationer_type' => User::class,
            'date' => $monday->copy()->addDay()->toDateString(),
            'type' => 'OFF_WORK',
            'full_day' => true,
        ]);

        $singleResult = $this->service->calculateWeeklyWorkingHours($user, $startDate, $endDate);

        // Mock method to call private precomputeWeeklyWorkingHours via Reflection or just test the public method that uses it
        // getUsersWithPlannedWorkingHours uses precomputeWeeklyWorkingHours
        $usersResult = $this->service->getUsersWithPlannedWorkingHours($startDate, $endDate, UserShiftPlanResource::class, true, null);

        $userData = collect($usersResult)->firstWhere('id', $user->id);
        $this->assertNotNull($userData);

        $weekNum = ltrim($monday->format('W'), '0');
        $this->assertEquals($singleResult[$weekNum]['planned'], $userData['weeklyWorkingHours'][$weekNum]['planned']);
        $this->assertEquals($singleResult[$weekNum]['daily_target'], $userData['weeklyWorkingHours'][$weekNum]['daily_target']);

        // Geplant: 2h + 0.5h = 2.5h = 2h 30m
        $this->assertEquals('2h 30m', $userData['weeklyWorkingHours'][$weekNum]['planned']);
    }
}
