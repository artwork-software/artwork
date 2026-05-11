<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\User\Models\User;
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
}
