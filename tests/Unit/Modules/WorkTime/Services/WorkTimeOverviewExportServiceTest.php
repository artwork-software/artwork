<?php

namespace Tests\Unit\Modules\WorkTime\Services;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Artwork\Modules\WorkTime\Services\WorkTimeOverviewExportService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WorkTimeOverviewExportServiceTest extends TestCase
{
    private WorkTimeOverviewExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WorkTimeOverviewExportService::class);
    }

    private function createCraftWorker(Craft $craft, bool $isFreelancer = false): User
    {
        $user = User::factory()->create([
            'can_work_shifts' => true,
            'is_freelancer' => $isFreelancer,
        ]);
        $craft->users()->attach($user->id);

        return $user;
    }

    private function insertBooking(User $user, string $day, int $wantedMinutes, int $workedMinutes): void
    {
        WorkTimeBooking::query()->insert([
            'user_id' => $user->id,
            'booking_day' => $day,
            'booking_weekday' => Carbon::parse($day)->dayOfWeek,
            'wanted_working_hours' => $wantedMinutes,
            'worked_hours' => $workedMinutes,
            'work_time_balance_change' => $workedMinutes - $wantedMinutes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assignShift(
        Craft $craft,
        User|Freelancer $worker,
        string $day,
        string $start,
        string $end,
        ?string $endDay = null,
        int $breakMinutes = 60,
    ): void {
        $endDay ??= $day;
        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => $day,
            'end_date' => $endDay,
            'start' => $start,
            'end' => $end,
            'break_minutes' => $breakMinutes,
        ]);

        $qualification = ShiftQualification::factory()->create();
        $pivotAttributes = [
            'shift_qualification_id' => $qualification->id,
            'start_date' => $day,
            'end_date' => $endDay,
            'start_time' => $start,
            'end_time' => $end,
        ];

        if ($worker instanceof Freelancer) {
            $shift->freelancer()->attach($worker->id, $pivotAttributes);
        } else {
            $shift->users()->attach($worker->id, $pivotAttributes);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMatrix(Craft $craft, string $start, string $end): array
    {
        return $this->service->buildMatrix(
            Carbon::parse($start),
            Carbon::parse($end),
            [$craft->id],
            'en'
        );
    }

    #[Test]
    public function itSumsBookingsPerMonthIntoInternalCells(): void
    {
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);

        $this->insertBooking($user, '2026-05-04', 480, 510);
        $this->insertBooking($user, '2026-05-05', 480, 450);
        $this->insertBooking($user, '2026-06-01', 480, 480);

        $matrix = $this->buildMatrix($craft, '2026-05-01', '2026-06-30');

        $this->assertSame([['id' => $craft->id, 'name' => $craft->name]], $matrix['crafts']->all());

        $mayRow = $matrix['rows']->first(fn (array $row) => $row['label'] === 'May 2026');
        $this->assertNotNull($mayRow);
        $this->assertSame(960, $mayRow['cells'][$craft->id]['soll_intern']);
        $this->assertSame(960, $mayRow['cells'][$craft->id]['ist_intern']);
        $this->assertSame(0, $mayRow['cells'][$craft->id]['ist_extern']);
        $this->assertSame(960, $mayRow['total']['soll_intern']);

        $juneRow = $matrix['rows']->first(fn (array $row) => $row['label'] === 'June 2026');
        $this->assertSame(480, $juneRow['cells'][$craft->id]['soll_intern']);
    }

    #[Test]
    public function itCountsBookingsOfMultiCraftUsersOnlyOnce(): void
    {
        $craftA = Craft::factory()->create(['position' => 1]);
        $craftB = Craft::factory()->create(['position' => 2]);
        $user = $this->createCraftWorker($craftA);
        $craftB->users()->attach($user->id);

        $this->insertBooking($user, '2026-05-04', 480, 510);
        // Schichtminuten nur in Gewerk B → Buchung wird B zugeordnet, nicht doppelt gezählt
        $this->assignShift($craftB, $user, '2026-05-04', '09:00:00', '18:00:00');

        $matrix = $this->service->buildMatrix(
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31'),
            [$craftA->id, $craftB->id],
            'en'
        );

        $mayRow = $matrix['rows']->first(fn (array $row) => $row['label'] === 'May 2026');
        $this->assertSame(0, $mayRow['cells'][$craftA->id]['ist_intern']);
        $this->assertSame(510, $mayRow['cells'][$craftB->id]['ist_intern']);
        $this->assertSame(510, $mayRow['total']['ist_intern']);
        $this->assertSame(480, $mayRow['total']['soll_intern']);
    }

    #[Test]
    public function itAppendsAYearSumRowPerYear(): void
    {
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);

        $this->insertBooking($user, '2025-12-01', 480, 480);
        $this->insertBooking($user, '2026-01-05', 480, 540);

        $matrix = $this->buildMatrix($craft, '2025-12-01', '2026-01-31');

        $labels = $matrix['rows']->pluck('label')->all();
        $this->assertSame(['December 2025', '2025', 'January 2026', '2026'], $labels);

        $sum2025 = $matrix['rows']->first(fn (array $row) => $row['is_sum'] && $row['label'] === '2025');
        $this->assertSame(480, $sum2025['cells'][$craft->id]['ist_intern']);

        $sum2026 = $matrix['rows']->first(fn (array $row) => $row['is_sum'] && $row['label'] === '2026');
        $this->assertSame(540, $sum2026['cells'][$craft->id]['ist_intern']);
        $this->assertSame(480, $sum2026['cells'][$craft->id]['soll_intern']);
    }

    #[Test]
    public function itCalculatesExternalHoursFromShifts(): void
    {
        $craft = Craft::factory()->create();
        $freelancer = Freelancer::factory()->create(['can_work_shifts' => true]);
        $craft->freelancers()->attach($freelancer->id);

        // 10:00 - 18:00 with 60 minutes break = 420 worked minutes
        $this->assignShift($craft, $freelancer, '2026-06-10', '10:00', '18:00');

        $matrix = $this->buildMatrix($craft, '2026-06-01', '2026-06-30');

        $juneRow = $matrix['rows']->first(fn (array $row) => !$row['is_sum']);
        $this->assertSame(420, $juneRow['cells'][$craft->id]['ist_extern']);
        $this->assertSame(0, $juneRow['cells'][$craft->id]['soll_extern']);
        $this->assertSame(0, $juneRow['cells'][$craft->id]['ist_intern']);
        $this->assertSame(0, $juneRow['cells'][$craft->id]['soll_intern']);
    }

    #[Test]
    public function itFallsBackToShiftMinutesForUsersWithoutBookings(): void
    {
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);

        $this->assignShift($craft, $user, '2026-06-10', '10:00', '18:00');

        $matrix = $this->buildMatrix($craft, '2026-06-01', '2026-06-30');

        $juneRow = $matrix['rows']->first(fn (array $row) => !$row['is_sum']);
        $this->assertSame(420, $juneRow['cells'][$craft->id]['ist_intern']);
        $this->assertSame(0, $juneRow['cells'][$craft->id]['soll_intern']);
    }

    #[Test]
    public function unbookedDaysOfAPartlyBookedMonthStillCountTheirShifts(): void
    {
        // Vorher: sobald ein Monat eine Buchung hatte, fielen die Schichten aller übrigen Tage weg
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);
        $this->insertBooking($user, '2026-06-01', 480, 480);
        $this->assignShift($craft, $user, '2026-06-01', '09:00', '18:00'); // gebucht: nicht doppelt
        $this->assignShift($craft, $user, '2026-06-20', '10:00', '18:00'); // nicht gebucht: 420

        $juneRow = $this->buildMatrix($craft, '2026-06-01', '2026-06-30')['rows']->first(fn (array $row) => !$row['is_sum']);

        $this->assertSame(480 + 420, $juneRow['cells'][$craft->id]['ist_intern']);
        $this->assertSame(480, $juneRow['cells'][$craft->id]['soll_intern']);
    }

    #[Test]
    public function individualTimesOfUnbookedDaysCountOnceInThePrimaryCraft(): void
    {
        $craft = Craft::factory()->create(['position' => 1]);
        $otherCraft = Craft::factory()->create(['position' => 2]);
        $user = $this->createCraftWorker($craft);
        $otherCraft->users()->attach($user->id);
        $user->individualTimes()->create([
            'title' => 'Probe',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-10',
            'start_time' => '10:00',
            'end_time' => '13:00',
            'full_day' => false,
            'working_time_minutes' => 180,
            'break_minutes' => 0,
        ]);

        $matrix = $this->service->buildMatrix(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-30'),
            [$craft->id, $otherCraft->id],
            'en'
        );
        $juneRow = $matrix['rows']->first(fn (array $row) => !$row['is_sum']);

        $this->assertSame(180, $juneRow['cells'][$craft->id]['ist_intern']);
        $this->assertSame(0, $juneRow['cells'][$otherCraft->id]['ist_intern']);
    }

    #[Test]
    public function legacyCorrectionBookingsCountTheirDeltaAsActualHours(): void
    {
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);
        $this->insertBooking($user, '2026-06-01', 480, 480);
        WorkTimeBooking::query()->insert([
            'user_id' => $user->id,
            'name' => 'adjustment_work_time_change_request_1',
            'booking_day' => '2026-06-02',
            'booking_weekday' => 2,
            'wanted_working_hours' => 0,
            'worked_hours' => 0,
            'work_time_balance_change' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $juneRow = $this->buildMatrix($craft, '2026-06-01', '2026-06-30')['rows']->first(fn (array $row) => !$row['is_sum']);

        $this->assertSame(540, $juneRow['cells'][$craft->id]['ist_intern']);
    }

    #[Test]
    public function itIncludesOverlappingShiftsAndSplitsTheirMinutesAcrossMonths(): void
    {
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);

        // Four hours across the month boundary, minus a 60-minute break: like the time account the break is
        // deducted on the first day (60 in March), the part after midnight counts fully (120 in April).
        $this->assignShift(
            $craft,
            $user,
            '2026-03-31',
            '22:00',
            '02:00',
        );

        $matrix = $this->buildMatrix($craft, '2026-03-01', '2026-04-30');

        $marchRow = $matrix['rows']->first(fn (array $row) => $row['label'] === 'March 2026');
        $aprilRow = $matrix['rows']->first(fn (array $row) => $row['label'] === 'April 2026');
        $this->assertSame(60, $marchRow['cells'][$craft->id]['ist_intern']);
        $this->assertSame(120, $aprilRow['cells'][$craft->id]['ist_intern']);

        $overlapOnlyMatrix = $this->buildMatrix($craft, '2026-04-01', '2026-04-30');
        $overlapOnlyAprilRow = $overlapOnlyMatrix['rows']->first(fn (array $row) => !$row['is_sum']);
        $this->assertSame(120, $overlapOnlyAprilRow['cells'][$craft->id]['ist_intern']);
    }

    #[Test]
    public function itOnlyCountsShiftsForTheSelectedCraft(): void
    {
        $craft = Craft::factory()->create();
        $otherCraft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);
        $otherCraft->users()->attach($user->id);

        $this->assignShift($otherCraft, $user, '2026-06-10', '10:00', '18:00');

        $matrix = $this->buildMatrix($craft, '2026-06-01', '2026-06-30');
        $juneRow = $matrix['rows']->first(fn (array $row) => !$row['is_sum']);

        $this->assertSame(0, $juneRow['cells'][$craft->id]['ist_intern']);
    }

    #[Test]
    public function usersFlaggedAsFreelancerCountAsExternal(): void
    {
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft, isFreelancer: true);
        $this->insertBooking($user, '2026-05-04', 420, 480);

        $matrix = $this->buildMatrix($craft, '2026-05-01', '2026-05-31');

        $mayRow = $matrix['rows']->first(fn (array $row) => !$row['is_sum']);
        $this->assertSame(420, $mayRow['cells'][$craft->id]['soll_extern']);
        $this->assertSame(480, $mayRow['cells'][$craft->id]['ist_extern']);
        $this->assertSame(0, $mayRow['cells'][$craft->id]['soll_intern']);
        $this->assertSame(0, $mayRow['cells'][$craft->id]['ist_intern']);
        $this->assertSame(420, $mayRow['total']['soll_extern']);
    }

    #[Test]
    public function itOnlyExportsSelectedCraftsAndTotalsAcrossThem(): void
    {
        $craft = Craft::factory()->create();
        $otherCraft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);
        $otherUser = $this->createCraftWorker($otherCraft);

        $this->insertBooking($user, '2026-05-04', 480, 480);
        $this->insertBooking($otherUser, '2026-05-04', 480, 480);

        $matrix = $this->buildMatrix($craft, '2026-05-01', '2026-05-31');

        $this->assertSame([$craft->id], $matrix['crafts']->pluck('id')->all());

        $mayRow = $matrix['rows']->first(fn (array $row) => !$row['is_sum']);
        $this->assertArrayNotHasKey($otherCraft->id, $mayRow['cells']);
        $this->assertSame(480, $mayRow['total']['ist_intern']);
    }

    private function giveWeekdayPattern(User $user, string $validFrom = '2026-01-01', ?string $validUntil = null): void
    {
        \Artwork\Modules\User\Models\UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'monday' => '08:00',
            'tuesday' => '08:00',
            'wednesday' => '08:00',
            'thursday' => '08:00',
            'friday' => '08:00',
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertDailyBooking(User $user, string $day, int $wanted, int $worked, ?int $change = null): void
    {
        WorkTimeBooking::query()->insert([
            'user_id' => $user->id,
            'name' => 'daily_work_time_booking_' . $day,
            'booking_day' => $day,
            'booking_weekday' => Carbon::parse($day)->dayOfWeek,
            'wanted_working_hours' => $wanted,
            'worked_hours' => $worked,
            'work_time_balance_change' => $change ?? $worked - $wanted,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function theCurrentMonthCountsOnlyPastUnbookedDaysWithTheirTarget(): void
    {
        // AZ-2: Ist zählte Schichten künftiger Tage, Soll nur Buchungen (09.10.: Soll 48 h, Ist 176 h)
        $this->travelTo(Carbon::parse('2026-06-10 12:00'));
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);
        $this->giveWeekdayPattern($user);
        $this->insertDailyBooking($user, '2026-06-01', 480, 480);
        $this->assignShift($craft, $user, '2026-06-01', '09:00', '18:00'); // gebucht: zählt aus der Buchung
        $this->assignShift($craft, $user, '2026-06-03', '10:00', '18:00'); // vergangen, nicht gebucht: 420
        $this->assignShift($craft, $user, '2026-06-10', '10:00', '18:00'); // heute: noch nicht
        $this->assignShift($craft, $user, '2026-06-15', '10:00', '18:00'); // künftig: nicht

        $juneRow = $this->buildMatrix($craft, '2026-06-01', '2026-06-30')['rows']->first(fn (array $row) => !$row['is_sum']);

        // Soll: gebuchter 01.06. + sechs nicht gebuchte vergangene Werktage (02.–05., 08., 09.) je 8 h
        $this->assertSame(480 + 6 * 480, $juneRow['cells'][$craft->id]['soll_intern']);
        $this->assertSame(480 + 420, $juneRow['cells'][$craft->id]['ist_intern']);
    }

    #[Test]
    public function aNeverBookedPastDayCountsTheTargetOfTheWorkTimesTab(): void
    {
        $this->travelTo(Carbon::parse('2026-06-10 12:00'));
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);
        $this->giveWeekdayPattern($user, '2026-05-04', '2026-05-04'); // nur Montag, 04.05., hat ein Muster
        $this->insertDailyBooking($user, '2026-04-30', 0, 0); // Zeitkonto läuft schon vor dem Zeitraum
        \Artwork\Modules\Vacation\Models\Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-05-04',
            'full_day' => true,
            'type' => 'OFF_WORK',
        ]);

        $mayRow = $this->buildMatrix($craft, '2026-05-01', '2026-05-31')['rows']->first(fn (array $row) => !$row['is_sum']);
        $tabDay = app(\Artwork\Modules\WorkTime\Services\WorkTimeCalculationService::class)
            ->dayBreakdown($user, Carbon::parse('2026-05-04'));

        $this->assertSame(480, $mayRow['cells'][$craft->id]['soll_intern']);
        $this->assertSame($tabDay['target'], $mayRow['cells'][$craft->id]['soll_intern']);
        $this->assertSame($tabDay['actual'], $mayRow['cells'][$craft->id]['ist_intern']); // Urlaub: Ist = Soll
    }

    #[Test]
    public function aLegacySickRowShowsTheSameTargetAsTheWorkTimesTab(): void
    {
        // M4: frühere Krank-Logik (Ist 0, Soll 480, Saldo 0) – Tab zeigt Soll = Ist − Saldo = 0
        $this->travelTo(Carbon::parse('2026-06-10 12:00'));
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);
        $this->insertDailyBooking($user, '2026-05-04', 480, 0, 0);

        $mayRow = $this->buildMatrix($craft, '2026-05-01', '2026-05-31')['rows']->first(fn (array $row) => !$row['is_sum']);
        $tabDay = app(\Artwork\Modules\WorkTime\Services\WorkTimeCalculationService::class)
            ->dayBreakdown($user, Carbon::parse('2026-05-04'));

        $this->assertSame(0, $tabDay['target']);
        $this->assertSame($tabDay['target'], $mayRow['cells'][$craft->id]['soll_intern']);
        $this->assertSame($tabDay['actual'], $mayRow['cells'][$craft->id]['ist_intern']);
    }

    #[Test]
    public function aDuplicateDailyRowCountsLikeInTheWorkTimesTab(): void
    {
        $this->travelTo(Carbon::parse('2026-06-10 12:00'));
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);
        $this->insertDailyBooking($user, '2026-05-04', 480, 0);
        $this->insertDailyBooking($user, '2026-05-04', 480, 0);

        $mayRow = $this->buildMatrix($craft, '2026-05-01', '2026-05-31')['rows']->first(fn (array $row) => !$row['is_sum']);
        $tabDay = app(\Artwork\Modules\WorkTime\Services\WorkTimeCalculationService::class)
            ->dayBreakdown($user, Carbon::parse('2026-05-04'));

        $this->assertSame($tabDay['target'], $mayRow['cells'][$craft->id]['soll_intern']);
        $this->assertSame($tabDay['actual'], $mayRow['cells'][$craft->id]['ist_intern']);
    }

    #[Test]
    public function aDayBeforeTheFirstDailyBookingHasNoTargetButKeepsItsShiftMinutes(): void
    {
        // Vor Beginn des Zeitkontos (erste Tagesbuchung) kein Soll – wie „nicht gebucht“ im Arbeitszeiten-Tab
        $this->travelTo(Carbon::parse('2026-06-10 12:00'));
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);
        $this->giveWeekdayPattern($user);
        $this->assignShift($craft, $user, '2026-06-02', '10:00', '18:00'); // vor Kontobeginn: 420 im Ist
        \Artwork\Modules\Vacation\Models\Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-06-01',
            'full_day' => true,
            'type' => 'OFF_WORK',
        ]); // vor Kontobeginn: kein soll-neutrales Ist ohne Soll
        $this->insertDailyBooking($user, '2026-06-03', 480, 480);

        $juneRow = $this->buildMatrix($craft, '2026-06-01', '2026-06-30')['rows']->first(fn (array $row) => !$row['is_sum']);

        // Soll: gebuchter 03.06. + nie gebuchte Werktage danach (04., 05., 08., 09.); 01. und 02.06. ohne Soll
        $this->assertSame(480 + 4 * 480, $juneRow['cells'][$craft->id]['soll_intern']);
        $this->assertSame(480 + 420, $juneRow['cells'][$craft->id]['ist_intern']);
    }

    #[Test]
    public function aNeverBookedDayAfterTheFirstDailyBookingKeepsItsTarget(): void
    {
        $this->travelTo(Carbon::parse('2026-06-10 12:00'));
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);
        $this->giveWeekdayPattern($user, '2026-06-08', '2026-06-09');
        $this->insertDailyBooking($user, '2026-06-05', 0, 0);

        $juneRow = $this->buildMatrix($craft, '2026-06-01', '2026-06-30')['rows']->first(fn (array $row) => !$row['is_sum']);

        // 08. und 09.06. nie gebucht, aber nach Kontobeginn: Soll aus dem Muster
        $this->assertSame(2 * 480, $juneRow['cells'][$craft->id]['soll_intern']);
        $this->assertSame(0, $juneRow['cells'][$craft->id]['ist_intern']);
    }

    #[Test]
    public function aPersonWithoutAnyDailyBookingGetsNoTarget(): void
    {
        $this->travelTo(Carbon::parse('2026-06-10 12:00'));
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($craft);
        $this->giveWeekdayPattern($user);
        $this->assignShift($craft, $user, '2026-06-03', '10:00', '18:00');
        // Korrekturzeile ist keine Tagesbuchung und startet kein Zeitkonto
        WorkTimeBooking::query()->insert([
            'user_id' => $user->id,
            'name' => 'adjustment_work_time_change_request_1',
            'booking_day' => '2026-06-02',
            'booking_weekday' => 2,
            'wanted_working_hours' => 0,
            'worked_hours' => 0,
            'work_time_balance_change' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $juneRow = $this->buildMatrix($craft, '2026-06-01', '2026-06-30')['rows']->first(fn (array $row) => !$row['is_sum']);

        $this->assertSame(0, $juneRow['cells'][$craft->id]['soll_intern']);
        $this->assertSame(420 + 30, $juneRow['cells'][$craft->id]['ist_intern']);
    }
}
