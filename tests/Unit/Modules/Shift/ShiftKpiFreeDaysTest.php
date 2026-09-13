<?php

namespace Tests\Unit\Modules\Shift;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\ShiftKpiTrackingService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Block 4 (NV Bühne): "Ganze freie Tage" je Spielzeithälfte = Kalendertage ohne Schicht (Pivot-Zeiten,
 * Schicht über Mitternacht belegt beide Tage) und ohne individuelle Zeit; Ziel = free_full_days_per_week ×
 * Wochen der Hälfte. "Gewährte halbe freie Tage" bekommen das Ziel free_half_days_per_week × Wochen.
 */
final class ShiftKpiFreeDaysTest extends TestCase
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

    private function user(): User
    {
        $user = User::factory()->create(['can_work_shifts' => true]);
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

    private function contract(User $user, int $fullDays, int $halfDays): void
    {
        $template = UserContract::create([
            'name' => 'NV Bühne',
            'free_full_days_per_week' => $fullDays,
            'free_half_days_per_week' => $halfDays,
            'special_day_rule_active' => true,
            'compensation_period' => 90,
            'free_sundays_per_season' => 0,
            'days_off_first_26_weeks' => 0,
        ]);
        UserContractAssign::create([
            'user_id' => $user->id,
            'user_contract_id' => $template->id,
            'free_full_days_per_week' => $fullDays,
            'free_half_days_per_week' => $halfDays,
            'special_day_rule_active' => true,
            'compensation_period' => 90,
            'free_sundays_per_season' => 0,
            'days_off_first_26_weeks' => 0,
        ]);
    }

    private function shift(User $user, string $startDate, string $startTime, string $endDate, string $endTime): void
    {
        $shift = Shift::factory()->create([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start' => $startTime . ':00',
            'end' => $endTime . ':00',
            'break_minutes' => 0,
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

    private function individualTime(User $user, string $date): void
    {
        $user->individualTimes()->create([
            'title' => 'Einsatz',
            'start_date' => $date,
            'end_date' => $date,
            'full_day' => true,
            'working_time_minutes' => 480,
            'break_minutes' => 0,
        ]);
    }

    #[Test]
    public function season_halves_are_measured_in_weeks_with_one_decimal(): void
    {
        $halves = $this->service()->seasonHalves($this->seasonStart, $this->seasonEnd);

        // Mittelpunkt (getSeasonMidpoint, Spielzeitende = Tagesende) 30.01.2026: Hälfte 1 = 01.08.–29.01. (182 Tage), Hälfte 2 = 30.01.–31.07. (183 Tage)
        $this->assertSame('2025-08-01', $halves['half1']['start']);
        $this->assertSame('2026-01-29', $halves['half1']['end']);
        $this->assertSame(182, $halves['half1']['days']);
        $this->assertSame(26.0, $halves['half1']['weeks']);
        $this->assertSame('2026-01-30', $halves['half2']['start']);
        $this->assertSame('2026-07-31', $halves['half2']['end']);
        $this->assertSame(183, $halves['half2']['days']);
        $this->assertSame(26.1, $halves['half2']['weeks']);
    }

    #[Test]
    public function contract_week_values_become_targets_per_half(): void
    {
        $user = $this->user();
        $this->contract($user, 2, 1);

        $kpis = $this->service()->computeForUser($user->fresh(), $this->seasonStart, $this->seasonEnd);
        $targets = $kpis['targets'];

        $this->assertTrue($targets['free_full_days_per_week']['active']);
        $this->assertSame(2, $targets['free_full_days_per_week']['value']);
        $this->assertSame(52, $targets['free_full_days_per_week']['target_half1']); // 2 × 26,0
        $this->assertSame(52, $targets['free_full_days_per_week']['target_half2']); // 2 × 26,1 = 52,2 -> 52

        $this->assertTrue($targets['free_half_days_per_week']['active']);
        $this->assertSame(26, $targets['free_half_days_per_week']['target_half1']);
        $this->assertSame(26, $targets['free_half_days_per_week']['target_half2']);

        $this->assertSame($kpis['season_halves']['half2']['weeks'], 26.1);
    }

    #[Test]
    public function a_zero_contract_value_means_no_target(): void
    {
        $user = $this->user();
        $this->contract($user, 0, 0);

        $targets = $this->service()->computeForUser($user->fresh(), $this->seasonStart, $this->seasonEnd)['targets'];

        $this->assertFalse($targets['free_full_days_per_week']['active']);
        $this->assertSame(0, $targets['free_full_days_per_week']['target_half1']);
        $this->assertFalse($targets['free_half_days_per_week']['active']);
    }

    #[Test]
    public function targets_without_season_context_carry_no_half_values(): void
    {
        $user = $this->user();
        $this->contract($user, 2, 1);

        $targets = $this->service()->extractTargets($user->fresh());

        $this->assertSame(['active' => true, 'value' => 2], $targets['free_full_days_per_week']);
        $this->assertArrayNotHasKey('target_half1', $targets['free_half_days_per_week']);
    }

    #[Test]
    public function full_free_days_are_days_without_shift_and_without_individual_time(): void
    {
        $user = $this->user();
        $service = $this->service();

        $baseline = $service->computeForUser($user->fresh(), $this->seasonStart, $this->seasonEnd);
        // Abgeschlossene Tage: Hälfte 1 komplett (182), Hälfte 2 bis gestern (30.01.–20.07. = 172)
        $this->assertSame(182, $baseline['full_free_days_half1']);
        $this->assertSame(172, $baseline['full_free_days_half2']);

        // Schicht über Mitternacht belegt beide Tage (2. Hälfte)
        $this->shift($user, '2026-07-10', '22:00', '2026-07-11', '04:00');
        $afterShift = $service->computeForUser($user->fresh(), $this->seasonStart, $this->seasonEnd);
        $this->assertSame(170, $afterShift['full_free_days_half2']);
        $this->assertSame(182, $afterShift['full_free_days_half1']);

        // Individuelle Zeit zählt als belegt
        $this->individualTime($user, '2026-07-13');
        $afterIndividual = $service->computeForUser($user->fresh(), $this->seasonStart, $this->seasonEnd);
        $this->assertSame(169, $afterIndividual['full_free_days_half2']);

        // Zweite Schicht am bereits belegten Tag ändert nichts; Schicht in Hälfte 1 zählt dort
        $this->shift($user, '2026-07-13', '10:00', '2026-07-13', '12:00');
        $this->shift($user, '2025-09-05', '10:00', '2025-09-05', '12:00');
        $again = $service->computeForUser($user->fresh(), $this->seasonStart, $this->seasonEnd);
        $this->assertSame(169, $again['full_free_days_half2']);
        $this->assertSame(181, $again['full_free_days_half1']);
    }

    #[Test]
    public function vacation_and_future_days_do_not_change_full_free_days(): void
    {
        $user = $this->user();
        $service = $this->service();
        $baseline = $service->computeForUser($user->fresh(), $this->seasonStart, $this->seasonEnd);

        // Urlaub ist kein Einsatz -> Tag bleibt "frei" laut Definition
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-07-15',
            'type' => 'OFF_WORK',
            'full_day' => true,
            'is_series' => false,
            'comment' => null,
        ]);
        // Zukünftige Schicht (nach "heute") fließt nicht ein
        $this->shift($user, '2026-07-28', '10:00', '2026-07-28', '12:00');

        $kpis = $service->computeForUser($user->fresh(), $this->seasonStart, $this->seasonEnd);

        $this->assertSame($baseline['full_free_days_half1'], $kpis['full_free_days_half1']);
        $this->assertSame($baseline['full_free_days_half2'], $kpis['full_free_days_half2']);
    }
}
