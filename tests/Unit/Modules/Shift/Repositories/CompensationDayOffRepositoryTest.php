<?php

namespace Tests\Unit\Modules\Shift\Repositories;

use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Repositories\CompensationDayOffRepository;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CompensationDayOffRepositoryTest extends TestCase
{
    private CompensationDayOffRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(CompensationDayOffRepository::class);
    }

    #[Test]
    public function create_from_processing_sets_period_on_the_half_record_only(): void
    {
        $user = User::factory()->create();
        $violation = ShiftRuleViolation::factory()->create(['user_id' => $user->id]);

        // 1.5 days -> one full day (period must stay null) + one half day (period applies).
        $this->repository->createFromProcessing(
            $user->id,
            $violation->id,
            1.5,
            Carbon::now()->addDays(30)->toDateString(),
            'reason',
            false,
            'afternoon'
        );

        $records = CompensationDayOff::where('user_id', $user->id)->get();
        $this->assertCount(2, $records);

        $full = $records->firstWhere('value', '1.0');
        $half = $records->firstWhere('value', '0.5');

        $this->assertNotNull($full);
        $this->assertNull($full->half_day_period, 'full day must not carry a period');

        $this->assertNotNull($half);
        $this->assertSame('afternoon', $half->half_day_period);
    }

    #[Test]
    public function create_from_processing_half_day_persists_period(): void
    {
        $user = User::factory()->create();
        $violation = ShiftRuleViolation::factory()->create(['user_id' => $user->id]);

        $this->repository->createFromProcessing(
            $user->id,
            $violation->id,
            0.5,
            Carbon::now()->addDays(14)->toDateString(),
            null,
            false,
            'morning'
        );

        $half = CompensationDayOff::where('user_id', $user->id)->first();
        $this->assertNotNull($half);
        $this->assertSame('morning', $half->half_day_period);
    }

    #[Test]
    public function create_from_processing_ignores_period_for_full_day(): void
    {
        $user = User::factory()->create();
        $violation = ShiftRuleViolation::factory()->create(['user_id' => $user->id]);

        $this->repository->createFromProcessing(
            $user->id,
            $violation->id,
            1.0,
            Carbon::now()->addDays(7)->toDateString(),
            null,
            false,
            'morning'
        );

        $record = CompensationDayOff::where('user_id', $user->id)->first();
        $this->assertNotNull($record);
        $this->assertNull($record->half_day_period);
    }

    #[Test]
    public function get_granted_halves_for_user_in_range_returns_only_granted_halves_within_range(): void
    {
        $user = User::factory()->create();
        $inRange = Carbon::now()->startOfDay();

        // granted half inside range -> expected
        $expected = $this->grantedHalf($user, $inRange, 'morning');
        // granted full inside range -> excluded (value >= 1.0)
        CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => 1.0,
            'granted_date' => $inRange->toDateString(),
            'granted_at' => Carbon::now(),
            'deadline' => $inRange->copy()->addDays(30)->toDateString(),
        ]);
        // open (not granted) half inside range -> excluded (granted scope needs granted_at)
        CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => 0.5,
            'granted_date' => null,
            'deadline' => $inRange->copy()->addDays(30)->toDateString(),
        ]);
        // granted half OUTSIDE range -> excluded
        $this->grantedHalf($user, $inRange->copy()->addDays(40), 'afternoon');

        $result = $this->repository->getGrantedHalvesForUserInRange(
            $user->id,
            $inRange->copy()->subDay()->toDateString(),
            $inRange->copy()->addDay()->toDateString()
        );

        $this->assertCount(1, $result);
        $this->assertSame($expected->id, $result->first()->id);
    }

    private function grantedHalf(User $user, Carbon $date, string $period): CompensationDayOff
    {
        return CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => 0.5,
            'half_day_period' => $period,
            'granted_date' => $date->toDateString(),
            'granted_at' => Carbon::now(),
            'deadline' => $date->copy()->addDays(30)->toDateString(),
        ]);
    }
}
