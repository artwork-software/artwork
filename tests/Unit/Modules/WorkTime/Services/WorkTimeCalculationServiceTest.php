<?php

namespace Tests\Unit\Modules\WorkTime\Services;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Soll/Ist-Regeln (TVöD als Referenz): Fünftagewoche ohne Muster, Muster zum Datum,
 * Sondertage nur ohne Arbeit und nur mit aktiver Vertragsregel, Schulferien nie,
 * Krank/Urlaub soll-neutral, Buchung schlägt Schichten, Pause einmal am ersten Schichttag.
 */
final class WorkTimeCalculationServiceTest extends TestCase
{
    private const TUESDAY = '2026-07-21';

    private function service(): WorkTimeCalculationService
    {
        return app(WorkTimeCalculationService::class);
    }

    private function user(float $weeklyHours = 39.0): User
    {
        return User::factory()->create([
            'can_work_shifts' => true,
            'weekly_working_hours' => $weeklyHours,
        ]);
    }

    /**
     * @param array<string, string|null> $days z. B. ['tuesday' => '08:00']
     */
    private function workTime(User $user, array $days, string $validFrom = '2026-01-01', ?string $validUntil = null): void
    {
        UserWorkTime::query()->insert(array_merge([
            'user_id' => $user->id,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $days));
    }

    private function contract(User $user, bool $specialDayRule = true, bool $threeMonth = false, ?bool $assignRule = null): void
    {
        $template = UserContract::create([
            'name' => 'Testvertrag',
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => $specialDayRule,
            'compensation_period' => 90,
            'use_three_month_average_for_target_reduction' => $threeMonth,
            'free_sundays_per_season' => 0,
            'days_off_first_26_weeks' => 0,
        ]);
        UserContractAssign::create([
            'user_id' => $user->id,
            'user_contract_id' => $template->id,
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => $assignRule ?? $specialDayRule,
            'compensation_period' => 90,
            'free_sundays_per_season' => 0,
            'days_off_first_26_weeks' => 0,
        ]);
    }

    private function holiday(string $date, bool $special = true, ?string $endDate = null, string $name = 'Testfeiertag'): Holiday
    {
        return Holiday::create([
            'name' => $name,
            'date' => $date,
            'end_date' => $endDate ?? $date,
            'yearly' => false,
            'from_api' => false,
            'treatAsSpecialDay' => $special,
        ]);
    }

    private function individualTime(User $user, string $date, int $minutes): void
    {
        $user->individualTimes()->create([
            'title' => 'Einsatz',
            'start_date' => $date,
            'end_date' => $date,
            'full_day' => true,
            'working_time_minutes' => $minutes,
            'break_minutes' => 0,
        ]);
    }

    private function shift(User $user, string $startDate, string $startTime, string $endDate, string $endTime, int $break = 0): void
    {
        $shift = Shift::factory()->create([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start' => $startTime . ':00',
            'end' => $endTime . ':00',
            'break_minutes' => $break,
        ]);
        $qualification = ShiftQualification::query()->firstOrCreate(
            ['name' => 'Mitarbeiter'],
            ['icon' => 'IconUser', 'available' => true]
        );
        $user->shifts()->attach($shift->id, [
            'shift_qualification_id' => $qualification->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    private function booking(User $user, string $date, int $workedMinutes, int $wanted = 480): void
    {
        WorkTimeBooking::query()->create([
            'user_id' => $user->id,
            'name' => "booking_{$date}",
            'booking_day' => $date,
            'booking_weekday' => Carbon::parse($date)->dayOfWeek,
            'wanted_working_hours' => $wanted,
            'worked_hours' => $workedMinutes,
            'nightly_working_hours' => 0,
            'work_time_balance_change' => $workedMinutes - $wanted,
            'is_special_day' => false,
        ]);
    }

    private function vacation(User $user, string $date, string $type, bool $fullDay = true): void
    {
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => $date,
            'type' => $type,
            'full_day' => $fullDay,
            'is_series' => false,
            'comment' => null,
        ]);
    }

    #[Test]
    public function target_without_pattern_is_weekly_hours_divided_by_five_on_weekdays_and_zero_on_weekends(): void
    {
        $user = $this->user(39.0);

        $this->assertSame(468, $this->service()->targetMinutes($user, Carbon::parse('2026-07-20'))); // Montag 7:48 h
        $this->assertSame(468, $this->service()->targetMinutes($user, Carbon::parse('2026-07-24'))); // Freitag
        $this->assertSame(0, $this->service()->targetMinutes($user, Carbon::parse('2026-07-25'))); // Samstag
        $this->assertSame(0, $this->service()->targetMinutes($user, Carbon::parse('2026-07-26'))); // Sonntag
    }

    #[Test]
    public function target_with_pattern_uses_the_pattern_valid_at_the_date(): void
    {
        $user = $this->user();
        $this->workTime($user, ['monday' => '08:00', 'tuesday' => null], '2026-01-01', '2026-06-30');
        $this->workTime($user, ['monday' => '06:00', 'tuesday' => '05:30'], '2026-07-01');

        $service = $this->service();

        $this->assertSame(480, $service->targetMinutes($user, Carbon::parse('2026-06-15'))); // altes Muster
        $this->assertSame(0, $service->targetMinutes($user, Carbon::parse('2026-06-16'))); // Dienstag ohne Zeit = frei
        $this->assertSame(360, $service->targetMinutes($user, Carbon::parse('2026-07-20'))); // neues Muster
        $this->assertSame(330, $service->targetMinutes($user, Carbon::parse('2026-07-21')));
    }

    #[Test]
    public function special_day_without_work_zeroes_the_target_when_the_contract_rule_is_active(): void
    {
        $user = $this->user();
        $this->workTime($user, ['tuesday' => '08:00']);
        $this->contract($user, specialDayRule: true);
        $this->holiday(self::TUESDAY, true, null, 'Sommerfeiertag');

        $day = $this->service()->dayBreakdown($user, Carbon::parse(self::TUESDAY));

        $this->assertSame(0, $day['target']);
        $this->assertSame(480, $day['base_target']);
        $this->assertSame(480, $day['target_reduction']);
        $this->assertSame(WorkTimeCalculationService::REASON_SPECIAL_DAY, $day['reduction_reason']);
        $this->assertTrue($day['is_special_day']);
        $this->assertTrue($day['special_day_counts']);
        $this->assertSame('Sommerfeiertag', $day['special_day_name']);
        $this->assertSame(0, $day['actual']);
        $this->assertSame(0, $day['balance']);
    }

    #[Test]
    public function special_day_keeps_the_full_target_when_the_contract_rule_is_inactive(): void
    {
        $user = $this->user();
        $this->workTime($user, ['tuesday' => '08:00']);
        $this->contract($user, specialDayRule: false);
        $this->holiday(self::TUESDAY);

        $day = $this->service()->dayBreakdown($user, Carbon::parse(self::TUESDAY));

        $this->assertSame(480, $day['target']);
        $this->assertTrue($day['is_special_day']);
        $this->assertFalse($day['special_day_counts']);
        $this->assertNull($day['reduction_reason']);
    }

    #[Test]
    public function assignment_rule_wins_over_template_rule(): void
    {
        $user = $this->user();
        $this->workTime($user, ['tuesday' => '08:00']);
        // Vorlage aktiv, Zuweisung inaktiv -> Zuweisung gilt
        $this->contract($user, specialDayRule: true, assignRule: false);
        $this->holiday(self::TUESDAY);

        $this->assertSame(480, $this->service()->targetMinutes($user, Carbon::parse(self::TUESDAY)));
    }

    #[Test]
    public function work_on_a_special_day_keeps_the_full_target_and_counts_the_hours(): void
    {
        $user = $this->user();
        $this->workTime($user, ['tuesday' => '08:00']);
        $this->contract($user, specialDayRule: true);
        $this->holiday(self::TUESDAY);
        $this->individualTime($user, self::TUESDAY, 300);

        $day = $this->service()->dayBreakdown($user, Carbon::parse(self::TUESDAY));

        $this->assertSame(480, $day['target']);
        $this->assertSame(300, $day['actual']);
        $this->assertSame(-180, $day['balance']);
        $this->assertNull($day['reduction_reason']);
        $this->assertTrue($day['is_special_day']);
    }

    #[Test]
    public function school_vacation_without_flag_never_reduces_the_target(): void
    {
        $user = $this->user();
        $this->workTime($user, ['tuesday' => '08:00']);
        $this->contract($user, specialDayRule: true);
        $this->holiday('2026-07-06', false, '2026-08-14', 'Sommerferien');

        $day = $this->service()->dayBreakdown($user, Carbon::parse(self::TUESDAY));

        $this->assertFalse($day['is_special_day']);
        $this->assertSame(480, $day['target']);
    }

    #[Test]
    public function three_month_mode_reduces_the_target_by_the_same_weekday_average(): void
    {
        $user = $this->user();
        $this->workTime($user, ['tuesday' => '08:00', 'wednesday' => '08:00']);
        $this->contract($user, specialDayRule: true, threeMonth: true);
        $this->holiday(self::TUESDAY);

        // Dienstage im Referenzfenster April–Juni: 300 + 420 -> Ø 360; Mittwoch (900) zählt nicht
        $this->booking($user, '2026-04-07', 300);
        $this->booking($user, '2026-05-12', 420);
        $this->booking($user, '2026-06-10', 900);

        $day = $this->service()->dayBreakdown($user, Carbon::parse(self::TUESDAY));

        $this->assertSame(120, $day['target']);
        $this->assertSame(360, $day['target_reduction']);
        $this->assertSame(360, $day['reference_weekday_average']);
        $this->assertSame(['start' => '2026-04-01', 'end' => '2026-06-30'], $day['reference_period']);
        $this->assertSame(WorkTimeCalculationService::REASON_SPECIAL_DAY, $day['reduction_reason']);
    }

    #[Test]
    public function three_month_mode_falls_back_to_the_full_pattern_target_without_weekday_data(): void
    {
        $user = $this->user();
        $this->workTime($user, ['tuesday' => '08:00']);
        $this->contract($user, specialDayRule: true, threeMonth: true);
        $this->holiday(self::TUESDAY);
        $this->booking($user, '2026-06-10', 900); // Mittwoch -> kein Dienstagswert

        $day = $this->service()->dayBreakdown($user, Carbon::parse(self::TUESDAY));

        $this->assertSame(0, $day['target']);
        $this->assertSame(480, $day['target_reduction']);
    }

    #[Test]
    public function sick_day_is_target_neutral(): void
    {
        $user = $this->user();
        $this->workTime($user, ['tuesday' => '08:00']);
        $this->vacation($user, self::TUESDAY, 'NOT_AVAILABLE');

        $day = $this->service()->dayBreakdown($user, Carbon::parse(self::TUESDAY));

        $this->assertTrue($day['is_sick']);
        $this->assertSame(480, $day['target']);
        $this->assertSame(480, $day['actual']);
        $this->assertSame(0, $day['balance']);
    }

    #[Test]
    public function half_day_vacation_adds_half_the_target_to_the_work_done(): void
    {
        $user = $this->user();
        $this->workTime($user, ['tuesday' => '08:00']);
        $this->vacation($user, self::TUESDAY, 'OFF_WORK', fullDay: false);
        $this->individualTime($user, self::TUESDAY, 240);

        $day = $this->service()->dayBreakdown($user, Carbon::parse(self::TUESDAY));

        $this->assertTrue($day['is_vacation']);
        $this->assertSame(0.5, $day['vacation_factor']);
        $this->assertSame(480, $day['target']);
        $this->assertSame(480, $day['actual']); // 240 Arbeit + 0,5 · 480
        $this->assertSame(0, $day['balance']);
    }

    #[Test]
    public function full_day_vacation_is_target_neutral(): void
    {
        $user = $this->user();
        $this->workTime($user, ['tuesday' => '08:00']);
        $this->vacation($user, self::TUESDAY, 'OFF_WORK');

        $day = $this->service()->dayBreakdown($user, Carbon::parse(self::TUESDAY));

        $this->assertSame(480, $day['actual']);
        $this->assertSame(0, $day['balance']);
        $this->assertSame(1.0, $day['vacation_factor']);
    }

    #[Test]
    public function a_booking_beats_shifts_and_individual_times_without_double_counting(): void
    {
        $user = $this->user();
        $this->workTime($user, ['tuesday' => '08:00']);
        $this->shift($user, self::TUESDAY, '08:00', self::TUESDAY, '12:00');
        $this->individualTime($user, self::TUESDAY, 120);
        $this->booking($user, self::TUESDAY, 500);

        $day = $this->service()->dayBreakdown($user, Carbon::parse(self::TUESDAY));

        $this->assertTrue($day['has_booking']);
        $this->assertSame(500, $day['actual']);
        $this->assertSame(360, $day['work_minutes']); // Schicht 240 + Individualzeit 120 nur informativ
    }

    #[Test]
    public function without_a_booking_actual_is_shift_plus_individual_time(): void
    {
        $user = $this->user();
        $this->shift($user, self::TUESDAY, '08:00', self::TUESDAY, '12:30', 30);
        $this->individualTime($user, self::TUESDAY, 120);

        $this->assertSame(240 + 120, $this->service()->actualMinutes($user, Carbon::parse(self::TUESDAY)));
    }

    #[Test]
    public function break_is_deducted_once_on_the_first_day_of_a_multi_day_shift(): void
    {
        $user = $this->user();
        $this->shift($user, self::TUESDAY, '22:00', '2026-07-22', '04:00', 30);

        $range = $this->service()->breakdownForRange($user, Carbon::parse(self::TUESDAY), Carbon::parse('2026-07-22'));

        $this->assertSame(90, $range[self::TUESDAY]['actual']);   // 120 − 30 Pause
        $this->assertSame(240, $range['2026-07-22']['actual']);  // keine zweite Pause
    }

    #[Test]
    public function holiday_compensation_day_reduces_the_target_on_a_regular_day(): void
    {
        $user = $this->user();
        $this->workTime($user, ['tuesday' => '08:00']);
        $this->contract($user);
        CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => 1.0,
            'deadline' => '2026-08-31',
            'granted_date' => self::TUESDAY,
            'granted_at' => now(),
            'reason' => 'Ausgleichstag für Feiertagsarbeit',
            'for_holiday' => true,
        ]);

        $day = $this->service()->dayBreakdown($user, Carbon::parse(self::TUESDAY));

        $this->assertSame(0, $day['target']);
        $this->assertSame(WorkTimeCalculationService::REASON_COMPENSATION_DAY, $day['reduction_reason']);
    }

    #[Test]
    public function breakdown_for_range_covers_every_day_of_the_period(): void
    {
        $user = $this->user(40.0);

        $range = $this->service()->breakdownForRange($user, Carbon::parse('2026-07-20'), Carbon::parse('2026-07-26'));

        $this->assertSame(
            ['2026-07-20', '2026-07-21', '2026-07-22', '2026-07-23', '2026-07-24', '2026-07-25', '2026-07-26'],
            array_keys($range)
        );
        $this->assertSame(480, $range['2026-07-20']['target']);
        $this->assertSame(0, $range['2026-07-26']['target']);
    }

    #[Test]
    public function signed_hours_format_for_the_balance_badge(): void
    {
        $this->assertSame('+10:30 h', WorkTimeCalculationService::formatSignedHours(630));
        $this->assertSame("\u{2212}2:00 h", WorkTimeCalculationService::formatSignedHours(-120));
        $this->assertSame('+0:00 h', WorkTimeCalculationService::formatSignedHours(0));
    }
}
