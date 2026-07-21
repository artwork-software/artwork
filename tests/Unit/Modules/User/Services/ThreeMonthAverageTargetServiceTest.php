<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\ThreeMonthAverageTargetService;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ThreeMonthAverageTargetServiceTest extends TestCase
{
    #[Test]
    public function it_uses_only_the_three_completed_calendar_months(): void
    {
        $user = User::factory()->create();
        $this->booking($user, '2026-03-31', 900);
        $this->booking($user, '2026-04-10', 360);
        $this->booking($user, '2026-05-10', 480);
        $this->booking($user, '2026-06-10', 600);
        $this->booking($user, '2026-07-01', 1200);

        $service = app(ThreeMonthAverageTargetService::class);

        $this->assertSame(480, $service->averageMinutesFor($user, Carbon::parse('2026-07-21'), 420));
        $this->assertSame([
            'start' => '2026-04-01',
            'end' => '2026-06-30',
        ], $service->referencePeriodFor(Carbon::parse('2026-07-21')));
    }

    #[Test]
    public function it_excludes_sickness_days_from_the_average(): void
    {
        $user = User::factory()->create();
        $this->booking($user, '2026-04-10', 300);
        $this->booking($user, '2026-05-10', 900);

        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-05-10',
            'type' => 'NOT_AVAILABLE',
            'comment' => null,
        ]);

        $service = app(ThreeMonthAverageTargetService::class);

        $this->assertSame(300, $service->averageMinutesFor($user, Carbon::parse('2026-07-21'), 420));
    }

    #[Test]
    public function it_uses_the_contractual_target_when_no_worked_day_exists(): void
    {
        $user = User::factory()->create();

        $service = app(ThreeMonthAverageTargetService::class);

        $this->assertSame(450, $service->averageMinutesFor($user, Carbon::parse('2026-07-21'), 450));
    }

    #[Test]
    public function it_counts_an_individual_time_without_a_generated_work_time_booking(): void
    {
        $user = User::factory()->create();
        $this->booking($user, '2026-04-10', 360);
        $user->individualTimes()->create([
            'title' => 'Individuelle Arbeitszeit',
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-10',
            'start_time' => '09:00',
            'end_time' => '19:00',
            'full_day' => false,
            'working_time_minutes' => 600,
            'break_minutes' => 0,
        ]);
        $user->individualTimes()->create([
            'title' => 'Zweite individuelle Arbeitszeit',
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-10',
            'start_time' => '20:00',
            'end_time' => '22:00',
            'full_day' => false,
            'working_time_minutes' => 120,
            'break_minutes' => 0,
        ]);

        $service = app(ThreeMonthAverageTargetService::class);

        $this->assertSame(540, $service->averageMinutesFor($user, Carbon::parse('2026-07-21'), 420));
    }

    private function booking(User $user, string $date, int $workedMinutes): WorkTimeBooking
    {
        return WorkTimeBooking::query()->create([
            'user_id' => $user->id,
            'name' => "booking_{$date}",
            'booking_day' => $date,
            'booking_weekday' => Carbon::parse($date)->dayOfWeek,
            'wanted_working_hours' => 420,
            'worked_hours' => $workedMinutes,
            'nightly_working_hours' => 0,
            'work_time_balance_change' => $workedMinutes - 420,
            'is_special_day' => false,
        ]);
    }
}
