<?php

namespace Tests\Unit\Modules\WorkTime\Services;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Services\CraftDistributionExportService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CraftDistributionExportServiceTest extends TestCase
{
    private CraftDistributionExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CraftDistributionExportService::class);
    }

    private function createUniversalCraft(): Craft
    {
        return Craft::factory()->create(['universally_applicable' => true]);
    }

    private function createCraftWorker(Craft $craft): User
    {
        $user = User::factory()->create(['can_work_shifts' => true]);
        $craft->users()->attach($user->id);

        return $user;
    }

    private function assignShift(
        Craft $craft,
        User|Freelancer|ServiceProvider $worker,
        string $day,
        string $start,
        string $end,
        ?string $endDay = null,
        int $shiftCount = 1,
    ): void {
        $endDay ??= $day;
        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => $day,
            'end_date' => $endDay,
            'start' => $start,
            'end' => $end,
            'break_minutes' => 60,
        ]);

        $qualification = ShiftQualification::factory()->create();
        $pivotAttributes = [
            'shift_qualification_id' => $qualification->id,
            'start_date' => $day,
            'end_date' => $endDay,
            'start_time' => $start,
            'end_time' => $end,
        ];

        if ($worker instanceof ServiceProvider) {
            $shift->serviceProvider()->attach($worker->id, [
                ...$pivotAttributes,
                'shift_count' => $shiftCount,
            ]);
        } elseif ($worker instanceof Freelancer) {
            $shift->freelancer()->attach($worker->id, $pivotAttributes);
        } else {
            $shift->users()->attach($worker->id, $pivotAttributes);
        }
    }

    /**
     * @param array<int> $craftIds
     * @return array<string, mixed>
     */
    private function buildDistribution(Craft $universalCraft, string $start, string $end, array $craftIds = []): array
    {
        return $this->service->buildDistribution(
            Carbon::parse($start),
            Carbon::parse($end),
            $universalCraft->id,
            $craftIds,
        );
    }

    #[Test]
    public function itSplitsWorkerMinutesPerCraft(): void
    {
        $universalCraft = $this->createUniversalCraft();
        $craftA = Craft::factory()->create();
        $craftB = Craft::factory()->create();
        $user = $this->createCraftWorker($universalCraft);

        // 10:00 - 18:00 with 60 minutes break = 420 worked minutes each
        $this->assignShift($craftA, $user, '2026-06-10', '10:00', '18:00');
        $this->assignShift($craftB, $user, '2026-06-11', '10:00', '18:00');
        $this->assignShift($craftB, $user, '2026-06-12', '10:00', '18:00');

        $distribution = $this->buildDistribution(
            $universalCraft,
            '2026-06-08',
            '2026-06-14',
            [$craftA->id, $craftB->id],
        );

        $this->assertSame($universalCraft->id, $distribution['universalCraft']['id']);
        $this->assertCount(1, $distribution['rows']);

        $row = $distribution['rows']->first();
        $this->assertSame(trim($user->first_name . ' ' . $user->last_name), $row['name']);
        $this->assertSame(420, $row['minutes'][$craftA->id]);
        $this->assertSame(840, $row['minutes'][$craftB->id]);
        $this->assertSame(0, $row['other_minutes']);
        $this->assertSame(1260, $row['total_minutes']);
    }

    #[Test]
    public function itBucketsUnselectedCraftsIntoOther(): void
    {
        $universalCraft = $this->createUniversalCraft();
        $selectedCraft = Craft::factory()->create();
        $unselectedCraft = Craft::factory()->create();
        $user = $this->createCraftWorker($universalCraft);

        $this->assignShift($selectedCraft, $user, '2026-06-10', '10:00', '18:00');
        $this->assignShift($unselectedCraft, $user, '2026-06-11', '10:00', '18:00');

        $distribution = $this->buildDistribution(
            $universalCraft,
            '2026-06-08',
            '2026-06-14',
            [$selectedCraft->id],
        );

        $row = $distribution['rows']->first();
        $this->assertSame([$selectedCraft->id], $distribution['crafts']->pluck('id')->all());
        $this->assertSame(420, $row['minutes'][$selectedCraft->id]);
        $this->assertSame(420, $row['other_minutes']);
        $this->assertSame(840, $row['total_minutes']);
    }

    #[Test]
    public function itIncludesWorkersWithoutShiftsWithZeroMinutes(): void
    {
        $universalCraft = $this->createUniversalCraft();
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($universalCraft);

        $distribution = $this->buildDistribution($universalCraft, '2026-06-08', '2026-06-14', [$craft->id]);

        $this->assertCount(1, $distribution['rows']);

        $row = $distribution['rows']->first();
        $this->assertSame(trim($user->first_name . ' ' . $user->last_name), $row['name']);
        $this->assertSame(0, $row['minutes'][$craft->id]);
        $this->assertSame(0, $row['total_minutes']);
    }

    #[Test]
    public function itOnlyCountsShiftsWithinTheDateRange(): void
    {
        $universalCraft = $this->createUniversalCraft();
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($universalCraft);

        $this->assignShift($craft, $user, '2026-06-10', '10:00', '18:00');
        $this->assignShift($craft, $user, '2026-06-20', '10:00', '18:00');

        $distribution = $this->buildDistribution($universalCraft, '2026-06-08', '2026-06-14', [$craft->id]);

        $row = $distribution['rows']->first();
        $this->assertSame(420, $row['minutes'][$craft->id]);
    }

    #[Test]
    public function itClipsOverlappingShiftsToTheDateRange(): void
    {
        $universalCraft = $this->createUniversalCraft();
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($universalCraft);

        $this->assignShift($craft, $user, '2026-06-07', '22:00', '06:00', '2026-06-08');
        $this->assignShift($craft, $user, '2026-06-08', '22:00', '06:00', '2026-06-09');

        $distribution = $this->buildDistribution($universalCraft, '2026-06-08', '2026-06-08', [$craft->id]);

        $row = $distribution['rows']->first();
        // The 60-minute break is distributed proportionally across both
        // eight-hour shifts, so each four-hour overlap contributes 210 minutes.
        $this->assertSame(420, $row['minutes'][$craft->id]);
        $this->assertSame(420, $row['total_minutes']);
    }

    #[Test]
    public function itIncludesFreelancersOfTheUniversalCraft(): void
    {
        $universalCraft = $this->createUniversalCraft();
        $craft = Craft::factory()->create();
        $freelancer = Freelancer::factory()->create(['can_work_shifts' => true]);
        $universalCraft->freelancers()->attach($freelancer->id);

        $this->assignShift($craft, $freelancer, '2026-06-10', '10:00', '18:00');

        $distribution = $this->buildDistribution($universalCraft, '2026-06-08', '2026-06-14', [$craft->id]);

        $this->assertCount(1, $distribution['rows']);
        $this->assertSame(420, $distribution['rows']->first()['minutes'][$craft->id]);
    }

    #[Test]
    public function itMultipliesServiceProviderMinutesByShiftCount(): void
    {
        $universalCraft = $this->createUniversalCraft();
        $craft = Craft::factory()->create();
        $serviceProvider = ServiceProvider::factory()->create(['can_work_shifts' => true]);
        $universalCraft->serviceProviders()->attach($serviceProvider->id);

        $this->assignShift($craft, $serviceProvider, '2026-06-10', '10:00', '18:00', shiftCount: 3);

        $distribution = $this->buildDistribution($universalCraft, '2026-06-08', '2026-06-14', [$craft->id]);

        $this->assertCount(1, $distribution['rows']);
        $this->assertSame($serviceProvider->provider_name, $distribution['rows']->first()['name']);
        $this->assertSame(1260, $distribution['rows']->first()['minutes'][$craft->id]);
    }

    #[Test]
    public function itSumsAllWorkersIntoTheTotalRow(): void
    {
        $universalCraft = $this->createUniversalCraft();
        $craftA = Craft::factory()->create();
        $craftB = Craft::factory()->create();
        $firstUser = $this->createCraftWorker($universalCraft);
        $secondUser = $this->createCraftWorker($universalCraft);

        $this->assignShift($craftA, $firstUser, '2026-06-10', '10:00', '18:00');
        $this->assignShift($craftB, $secondUser, '2026-06-11', '10:00', '18:00');

        $distribution = $this->buildDistribution(
            $universalCraft,
            '2026-06-08',
            '2026-06-14',
            [$craftA->id, $craftB->id],
        );

        $this->assertCount(2, $distribution['rows']);
        $this->assertSame(420, $distribution['total']['minutes'][$craftA->id]);
        $this->assertSame(420, $distribution['total']['minutes'][$craftB->id]);
        $this->assertSame(0, $distribution['total']['other_minutes']);
        $this->assertSame(840, $distribution['total']['total_minutes']);
    }

    #[Test]
    public function itAnalyzesAllCraftsWhenNoCraftIdsAreGiven(): void
    {
        $universalCraft = $this->createUniversalCraft();
        $craft = Craft::factory()->create();
        $user = $this->createCraftWorker($universalCraft);

        $this->assignShift($craft, $user, '2026-06-10', '10:00', '18:00');

        $distribution = $this->buildDistribution($universalCraft, '2026-06-08', '2026-06-14');

        $this->assertContains($craft->id, $distribution['crafts']->pluck('id')->all());

        $row = $distribution['rows']->first();
        $this->assertSame(420, $row['minutes'][$craft->id]);
        $this->assertSame(0, $row['other_minutes']);
    }
}
