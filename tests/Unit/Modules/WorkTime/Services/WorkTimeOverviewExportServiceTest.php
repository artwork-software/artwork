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

    private function assignShift(Craft $craft, User|Freelancer $worker, string $day, string $start, string $end): void
    {
        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => $day,
            'end_date' => $day,
            'start' => $start,
            'end' => $end,
            'break_minutes' => 60,
        ]);

        $qualification = ShiftQualification::factory()->create();
        $pivotAttributes = [
            'shift_qualification_id' => $qualification->id,
            'start_date' => $day,
            'end_date' => $day,
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
}
