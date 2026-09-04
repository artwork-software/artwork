<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Services\ShiftKpiTrackingService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ShiftKpiTrackingServiceTest extends TestCase
{
    private Carbon $seasonStart;
    private Carbon $seasonEnd;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-07-21 12:00:00'));
        $this->seasonStart = Carbon::parse('2025-08-01');
        $this->seasonEnd = Carbon::parse('2026-07-31');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function service(): ShiftKpiTrackingService
    {
        return app(ShiftKpiTrackingService::class);
    }

    private function userWithWeekdayPattern(): User
    {
        $user = User::factory()->create(['can_work_shifts' => true, 'weekly_working_hours' => 40]);
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'monday' => '08:00',
            'tuesday' => '08:00',
            'wednesday' => '08:00',
            'thursday' => '08:00',
            'friday' => '08:00',
            'saturday' => null,
            'sunday' => null,
            'valid_from' => '2025-01-01',
            'valid_until' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function vacation(User $user, string $date, string $type, bool $fullDay = true, ?string $dayPart = null): void
    {
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => $date,
            'type' => $type,
            'full_day' => $fullDay,
            'day_part' => $dayPart,
            'is_series' => false,
            'comment' => null,
        ]);
    }

    #[Test]
    public function targets_come_from_the_assignment_before_the_template(): void
    {
        $user = $this->userWithWeekdayPattern();
        $template = UserContract::create([
            'name' => 'Vorlage',
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => true,
            'compensation_period' => 90,
            'free_sundays_per_season' => 8,
            'free_sundays_per_season_active' => true,
            'days_off_first_26_weeks' => 10,
            'one_and_half_day_combinations' => 5,
            'one_and_half_day_combinations_active' => true,
            'annual_vacation_days' => 30,
        ]);
        UserContractAssign::create([
            'user_id' => $user->id,
            'user_contract_id' => $template->id,
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => true,
            'compensation_period' => 90,
            'free_sundays_per_season' => 3,          // Zuweisung gewinnt
            'free_sundays_per_season_active' => true,
            'days_off_first_26_weeks' => 10,
            'one_and_half_day_combinations' => 2,
            'one_and_half_day_combinations_active' => false,
            'annual_vacation_days' => 28,
        ]);

        $targets = $this->service()->extractTargets($user->fresh());

        $this->assertSame(3, $targets['free_sundays_per_season']['value']);
        $this->assertTrue($targets['free_sundays_per_season']['active']);
        $this->assertSame(2, $targets['one_and_half_day_combinations']['value']);
        $this->assertFalse($targets['one_and_half_day_combinations']['active']);
        $this->assertSame(28, $targets['annual_vacation_days']['value']);
    }

    #[Test]
    public function targets_fall_back_to_the_template_when_the_assignment_field_is_unset(): void
    {
        $user = $this->userWithWeekdayPattern();
        $template = new UserContract([
            'name' => 'Vorlage',
            'one_and_half_day_combinations' => 5,
            'one_and_half_day_combinations_active' => true,
            'annual_vacation_days' => 30,
        ]);
        // Zuweisung ohne eigene Zielwerte (Felder nicht gesetzt) -> Vorlage
        $assign = new UserContractAssign(['free_sundays_per_season' => 3, 'free_sundays_per_season_active' => true]);
        $assign->setRelation('userContract', $template);
        $user->setRelation('contract', $assign);

        $targets = $this->service()->extractTargets($user);

        $this->assertSame(3, $targets['free_sundays_per_season']['value']);
        $this->assertSame(5, $targets['one_and_half_day_combinations']['value']);
        $this->assertTrue($targets['one_and_half_day_combinations']['active']);
        $this->assertSame(30, $targets['annual_vacation_days']['value']);
    }

    #[Test]
    public function free_sundays_only_count_with_a_free_saturday_or_monday(): void
    {
        $user = $this->userWithWeekdayPattern();

        // Sonntag 12.07. frei + Samstag 11.07. frei -> zählt (2. Hälfte); Montag 13.07. belegt
        $this->vacation($user, '2026-07-11', 'FREE_WORK');
        $this->vacation($user, '2026-07-12', 'FREE_WORK');
        $user->individualTimes()->create([
            'title' => 'Einsatz',
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-13',
            'full_day' => true,
            'working_time_minutes' => 480,
            'break_minutes' => 0,
        ]);

        // Sonntag 05.07. frei, aber Samstag mit Einsatz und Montag mit Einsatz -> zählt nicht
        $this->vacation($user, '2026-07-05', 'FREE_WORK');
        foreach (['2026-07-04', '2026-07-06'] as $occupied) {
            $user->individualTimes()->create([
                'title' => 'Einsatz',
                'start_date' => $occupied,
                'end_date' => $occupied,
                'full_day' => true,
                'working_time_minutes' => 480,
                'break_minutes' => 0,
            ]);
        }

        $kpis = $this->service()->computeForUser($user->fresh(), $this->seasonStart, $this->seasonEnd);

        $this->assertSame(1, $kpis['free_sundays_sat_mon_half2']);
        $this->assertSame(0, $kpis['free_sundays_sat_mon_half1']);
        $this->assertSame(2, $kpis['free_sundays_per_season']);
        $this->assertSame(1, $kpis['free_sundays_and_saturdays_season']);
    }

    #[Test]
    public function vacation_days_count_half_days_as_a_half(): void
    {
        $user = $this->userWithWeekdayPattern();
        $this->vacation($user, '2026-07-01', 'OFF_WORK', true);
        $this->vacation($user, '2026-07-02', 'OFF_WORK', false, 'morning');
        $this->vacation($user, '2026-08-03', 'OFF_WORK', true); // geplant (Zukunft)

        $service = $this->service();
        $kpis = $service->computeForUser($user->fresh(), $this->seasonStart, $this->seasonEnd);

        $this->assertSame(1.5, $kpis['granted_vacation_days_year']);

        $yearStart = Carbon::parse('2026-01-01');
        $yearEnd = Carbon::parse('2026-12-31');
        $this->assertSame(1.5, $service->grantedVacationUnitsForUser($user, $yearStart, $yearEnd));
        $this->assertSame(2.5, $service->grantedVacationUnitsForUser($user, $yearStart, $yearEnd, includePlanned: true));
    }

    #[Test]
    public function season_bounds_are_null_when_the_playing_time_window_is_not_configured(): void
    {
        $settings = app(\Artwork\Modules\GeneralSettings\Models\GeneralSettings::class);
        $settings->playing_time_window_start = '';
        $settings->playing_time_window_end = '';
        $settings->save();

        $this->assertNull($this->service()->getSeasonBounds());

        $settings->playing_time_window_start = '2025-08-01';
        $settings->playing_time_window_end = '2026-07-31';
        $settings->save();

        [$start, $end] = app(ShiftKpiTrackingService::class)->getSeasonBounds();
        $this->assertSame('2025-08-01', $start->toDateString());
        $this->assertSame('2026-07-31', $end->toDateString());
    }
}
