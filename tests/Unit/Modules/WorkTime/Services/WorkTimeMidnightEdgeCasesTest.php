<?php

namespace Tests\Unit\Modules\WorkTime\Services;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Core\Casts\TimeWithoutSeconds;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\WorkTime\Services\WorkTimeBookingService;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tageszuordnung von Schichten über Mitternacht, Pausen und Zeitumstellung (Europe/Berlin).
 * Erwartung jeweils als echte, gearbeitete Minuten je Kalendertag.
 */
final class WorkTimeMidnightEdgeCasesTest extends TestCase
{
    private function service(): WorkTimeCalculationService
    {
        return app(WorkTimeCalculationService::class);
    }

    private function userWithDailyTarget(): User
    {
        $user = User::factory()->create(['can_work_shifts' => true, 'work_time_balance' => 0]);
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'monday' => '08:00',
            'tuesday' => '08:00',
            'wednesday' => '08:00',
            'thursday' => '08:00',
            'friday' => '08:00',
            'saturday' => '08:00',
            'sunday' => '08:00',
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
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

    /**
     * @param array<string, int> $expected Tag => Minuten
     */
    #[Test]
    #[DataProvider('shifts')]
    public function minutes_are_attributed_to_the_calendar_days(array $shift, array $expected): void
    {
        $user = $this->userWithDailyTarget();
        $this->shift($user, ...$shift);
        $days = array_keys($expected);

        $all = $this->service()->shiftMinutesPerDay($user, Carbon::parse($days[0]), Carbon::parse(end($days)));
        foreach ($expected as $day => $minutes) {
            $this->assertSame($minutes, $all[$day] ?? 0, "Gesamtzeitraum, Tag {$day}");
            // Einzeltag-Abfrage (z. B. Nachtbuchung) muss dasselbe liefern
            $single = $this->service()->shiftMinutesPerDay($user, Carbon::parse($day), Carbon::parse($day));
            $this->assertSame($minutes, $single[$day] ?? 0, "Einzeltag {$day}");
        }
    }

    public static function shifts(): iterable
    {
        yield '22–06 mit 30 min Pause' => [
            ['2026-07-21', '22:00', '2026-07-22', '06:00', 30],
            ['2026-07-21' => 90, '2026-07-22' => 360],
        ];
        yield '23:30–07:30 mit 60 min Pause (Rest am Folgetag)' => [
            ['2026-07-21', '23:30', '2026-07-22', '07:30', 60],
            ['2026-07-21' => 0, '2026-07-22' => 420],
        ];
        yield 'Ende genau um Mitternacht' => [
            ['2026-07-21', '22:00', '2026-07-22', '00:00', 0],
            ['2026-07-21' => 120, '2026-07-22' => 0],
        ];
        yield 'über drei Kalendertage' => [
            ['2026-07-20', '20:00', '2026-07-22', '04:00', 60],
            ['2026-07-20' => 180, '2026-07-21' => 1440, '2026-07-22' => 240],
        ];
        yield 'Sommerzeit-Beginn 29.03. (Uhr springt 2→3)' => [
            ['2026-03-28', '22:00', '2026-03-29', '06:00', 0],
            ['2026-03-28' => 120, '2026-03-29' => 300],
        ];
        yield 'Sommerzeit-Ende 25.10. (2–3 Uhr doppelt)' => [
            ['2026-10-24', '22:00', '2026-10-25', '06:00', 0],
            ['2026-10-24' => 120, '2026-10-25' => 420],
        ];
    }

    #[Test]
    public function nightly_bookings_of_both_days_sum_up_to_the_whole_shift(): void
    {
        $user = $this->userWithDailyTarget();
        $this->shift($user, '2026-07-21', '22:00', '2026-07-22', '06:00', 30);
        $booking = app(WorkTimeBookingService::class);

        Carbon::setTestNow(Carbon::parse('2026-07-21 23:59:00'));
        $booking->calculateDailyWorkingHours();
        Carbon::setTestNow(Carbon::parse('2026-07-22 23:59:00'));
        $booking->calculateDailyWorkingHours();
        Carbon::setTestNow();

        $this->assertSame(450, (int) $user->workTimeBookings()->sum('worked_hours'));
        $this->assertSame(450 - 2 * 480, (int) $user->fresh()->work_time_balance);
    }

    #[Test]
    public function a_pivot_with_end_before_start_on_the_same_date_still_counts_as_overnight(): void
    {
        // Datenfehler-Fall: Ende 02:00 mit gleichem Enddatum wie Start (Ende liegt "vor" dem Start)
        $user = $this->userWithDailyTarget();
        $this->shift($user, '2026-07-21', '22:00', '2026-07-21', '02:00');

        $minutes = $this->service()->shiftMinutesPerDay($user, Carbon::parse('2026-07-21'), Carbon::parse('2026-07-22'));
        $this->assertSame(120, $minutes['2026-07-21'] ?? 0);
        $this->assertSame(120, $minutes['2026-07-22'] ?? 0);

        // Einzeltag-Kontext des Folgetags (Nachtbuchung) findet die Schicht ebenfalls
        $nextDay = $this->service()->dayBreakdown($user, Carbon::parse('2026-07-22'));
        $this->assertSame(120, $nextDay['shift_minutes']);
    }

    #[Test]
    public function an_overnight_individual_time_without_next_date_counts_into_the_next_day(): void
    {
        // Serien speicherten 22:00–04:00 mit Enddatum = Startdatum: vorher 0 Minuten
        $user = $this->userWithDailyTarget();
        $user->individualTimes()->create([
            'title' => 'Nachtprobe',
            'start_date' => '2026-07-21',
            'end_date' => '2026-07-21',
            'start_time' => '22:00',
            'end_time' => '04:00',
            'full_day' => false,
            'working_time_minutes' => 330,
            'break_minutes' => 30,
        ]);

        $range = $this->service()->individualMinutesPerDay($user, Carbon::parse('2026-07-21'), Carbon::parse('2026-07-22'));
        $nextDayOnly = $this->service()->dayBreakdown($user, Carbon::parse('2026-07-22'));

        $this->assertSame(90, $range['2026-07-21'] ?? 0);
        $this->assertSame(240, $range['2026-07-22'] ?? 0);
        $this->assertSame(240, $nextDayOnly['individual_minutes']);
    }

    #[Test]
    public function the_night_part_after_midnight_of_an_individual_time_is_booked_on_the_next_day(): void
    {
        $user = $this->userWithDailyTarget();
        $user->individualTimes()->create([
            'title' => 'Nachtprobe',
            'start_date' => '2026-07-21',
            'end_date' => '2026-07-22',
            'start_time' => '22:00',
            'end_time' => '04:00',
            'full_day' => false,
            'working_time_minutes' => 360,
            'break_minutes' => 0,
        ]);
        $booking = app(WorkTimeBookingService::class);

        Carbon::setTestNow(Carbon::parse('2026-07-21 23:59:00'));
        $booking->calculateDailyWorkingHours();
        Carbon::setTestNow(Carbon::parse('2026-07-22 23:59:00'));
        $booking->calculateDailyWorkingHours();
        Carbon::setTestNow();

        $night = $user->workTimeBookings()->orderBy('booking_day')->pluck('nightly_working_hours')->map(fn ($m): int => (int) $m)->all();
        $this->assertSame([120, 240], $night);
    }

    #[Test]
    public function times_between_two_and_three_oclock_survive_the_spring_dst_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-29 10:00:00')); // Sommerzeit-Beginn
        $user = User::factory()->create(['can_work_shifts' => true]);
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'sunday' => '02:30',
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(150, $this->service()->targetMinutes($user, Carbon::parse('2026-03-29')));
        $this->assertSame('02:30', (new TimeWithoutSeconds())->get(null, 'end_time', '02:30:00', []));
        Carbon::setTestNow();
    }
}
