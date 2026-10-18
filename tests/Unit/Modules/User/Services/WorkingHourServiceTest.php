<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\User\Services\WorkingHourService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WorkingHourServiceTest extends TestCase
{
    private WorkingHourService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WorkingHourService::class);
    }

    #[Test]
    public function convert_minutes_in_hours_formats_positive(): void
    {
        $this->assertSame('2h 30m', $this->service->convertMinutesInHours(150));
    }

    #[Test]
    public function convert_minutes_in_hours_formats_negative_with_sign(): void
    {
        $this->assertSame('-1h 30m', $this->service->convertMinutesInHours(-90));
    }

    #[Test]
    public function convert_minutes_in_hours_force_positive_drops_sign(): void
    {
        $this->assertSame('1h 30m', $this->service->convertMinutesInHours(-90, true));
    }

    #[Test]
    public function convert_minutes_in_hours_zero_is_zero(): void
    {
        $this->assertSame('0h 0m', $this->service->convertMinutesInHours(0));
    }

    #[Test]
    public function calculate_shift_time_returns_zero_when_no_shifts_or_bookings(): void
    {
        $user = User::factory()->create();

        $minutes = $this->service->calculateShiftTime(
            $user,
            Carbon::parse('2024-05-01'),
            Carbon::parse('2024-05-07')
        );

        $this->assertSame(0, $minutes);
    }

    #[Test]
    public function calculate_weekly_working_hours_returns_array_with_expected_keys(): void
    {
        $user = User::factory()->create([
            'weekly_working_hours' => 40,
        ]);

        $result = $this->service->calculateWeeklyWorkingHours(
            $user,
            Carbon::parse('2024-05-06'), // Monday
            Carbon::parse('2024-05-12'), // Sunday
        );

        $this->assertIsArray($result);
        // Should contain at least one week key
        $this->assertNotEmpty($result);
        $week = array_values($result)[0];
        $this->assertArrayHasKey('daily_target', $week);
        $this->assertArrayHasKey('planned', $week);
        $this->assertArrayHasKey('difference', $week);
        $this->assertArrayHasKey('isMinus', $week);
    }

    #[Test]
    public function a_cached_partial_week_is_not_served_for_the_full_week(): void
    {
        // Vorher: Monatsansicht Oktober cachte KW40 nur ab Do 01.10. unter "KW40" – die Wochenansicht
        // 28.09.–04.10. bekam danach bis zu 7 Tage lang die Teilwoche
        $user = User::factory()->create();
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'monday' => '08:00',
            'tuesday' => '08:00',
            'wednesday' => '08:00',
            'thursday' => '08:00',
            'friday' => '08:00',
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $partial = $this->service->calculateWeeklyWorkingHours($user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-04'));
        $full = $this->service->calculateWeeklyWorkingHours($user, Carbon::parse('2026-09-28'), Carbon::parse('2026-10-04'));

        $this->assertSame(2 * 480, $partial['40']['target_minutes']);
        $this->assertSame(5 * 480, $full['40']['target_minutes']);
    }
}
