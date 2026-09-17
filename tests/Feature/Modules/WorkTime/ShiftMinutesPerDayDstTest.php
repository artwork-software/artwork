<?php

namespace Tests\Feature\Modules\WorkTime;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Tagesfenster in shiftMinutesPerDay wurden mit festen 86400 Sekunden gebildet. Am Tag der
 * Zeitumstellung (23 bzw. 25 Stunden) verschob sich das Fenster, die Schicht fiel aus der
 * Zählung und Schichtregeln (z. B. Max-Stunden pro Tag) blieben an diesem Tag stumm.
 * Sichtbar wurde das über RevalidateShiftRulesJobTest, sobald heute + 40 Tage auf den
 * 25.10.2026 fiel.
 */
final class ShiftMinutesPerDayDstTest extends FeatureTestCase
{
    private function shiftOn(User $user, string $date, string $start, string $end, ?string $endDate = null): Shift
    {
        $shift = Shift::factory()->create([
            'event_id' => null,
            'start_date' => $date,
            'end_date' => $endDate ?? $date,
            'start' => $start,
            'end' => $end,
            'break_minutes' => 0,
        ]);
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'shift_count' => 1,
        ]);

        return $shift;
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function dstDays(): array
    {
        return [
            'Ende Sommerzeit 2026 (25 h)' => ['2026-10-25'],
            'Beginn Sommerzeit 2027 (23 h)' => ['2027-03-28'],
            'normaler Tag' => ['2026-11-04'],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('dstDays')]
    public function a_day_shift_counts_its_full_minutes_on_dst_change_days(string $day): void
    {
        $user = User::factory()->create();
        $this->shiftOn($user, $day, '08:00:00', '18:00:00');

        $minutes = app(WorkTimeCalculationService::class)
            ->shiftMinutesPerDay($user, Carbon::parse($day)->subDays(2), Carbon::parse($day)->addDays(2));

        $this->assertSame(600, $minutes[$day], "Schicht 08–18 Uhr am $day");
        $this->assertSame(0, $minutes[Carbon::parse($day)->addDay()->toDateString()]);
    }

    #[Test]
    public function a_night_shift_across_the_dst_change_is_split_at_midnight(): void
    {
        $user = User::factory()->create();
        // 24.10. 22:00 – 25.10. 06:00: 2 h am 24.10., 6 h Wanduhrzeit am 25.10.
        $this->shiftOn($user, '2026-10-24', '22:00:00', '06:00:00', '2026-10-25');

        $minutes = app(WorkTimeCalculationService::class)
            ->shiftMinutesPerDay($user, Carbon::parse('2026-10-24'), Carbon::parse('2026-10-25'));

        $this->assertSame(120, $minutes['2026-10-24']);
        // Zwischen 00:00 und 06:00 liegt am 25.10. eine Stunde mehr (03:00 → 02:00)
        $this->assertSame(420, $minutes['2026-10-25']);
    }
}
