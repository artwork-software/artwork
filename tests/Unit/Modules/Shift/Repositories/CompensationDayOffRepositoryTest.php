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

    #[Test]
    public function get_dashboard_stats_returns_counts_and_sums_per_status_in_a_single_query(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $today = Carbon::now()->startOfDay();

        // offen (Frist in der Zukunft): 1.0 + 0.5
        $this->entry($user, $today->copy()->addDays(10), 1.0);
        $this->entry($user, $today->copy()->addDays(12), 0.5);
        // überfällig (offen, Frist vorbei): 1.0 + 1.0 — zählen auch als offen
        $this->entry($user, $today->copy()->subDays(3), 1.0);
        $this->entry($user, $today->copy()->subDays(1), 1.0);
        // gewährt: 0.5 (Frist vorbei, aber gewährt → nicht überfällig)
        $this->entry($user, $today->copy()->subDays(5), 0.5, granted: true);
        // andere Person → über user_id-Filter ausgeschlossen
        $this->entry($other, $today->copy()->subDays(2), 1.0);
        $this->entry($other, $today->copy()->addDays(2), 1.0, granted: true);

        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $stats = $this->repository->getDashboardStats(['user_id' => $user->id]);
        $queries = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertSame(1, $queries, 'Kennzahlen müssen aus einer Abfrage kommen');
        $this->assertSame(4, $stats['open']);
        $this->assertSame(1, $stats['granted']);
        $this->assertSame(2, $stats['overdue']);
        $this->assertEqualsWithDelta(3.5, $stats['open_value'], 0.001);
        $this->assertEqualsWithDelta(0.5, $stats['granted_value'], 0.001);
        $this->assertEqualsWithDelta(2.0, $stats['overdue_value'], 0.001);

        // Ohne Filter (Altaufruf mit null = kein Gewerk): alle Personen
        $all = $this->repository->getDashboardStats(null);
        $this->assertSame(5, $all['open']);
        $this->assertSame(2, $all['granted']);
        $this->assertSame(3, $all['overdue']);

        // Leerer Filter-Treffer: Nullen statt null
        $none = $this->repository->getDashboardStats(['user_id' => $user->id + $other->id + 1000]);
        $this->assertSame(['open' => 0, 'granted' => 0, 'overdue' => 0, 'open_value' => 0.0, 'granted_value' => 0.0, 'overdue_value' => 0.0], $none);
    }

    private function entry(User $user, Carbon $deadline, float $value, bool $granted = false): CompensationDayOff
    {
        return CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => $value,
            'deadline' => $deadline->toDateString(),
            'granted_date' => $granted ? $deadline->copy()->subDay()->toDateString() : null,
            'granted_at' => $granted ? Carbon::now() : null,
        ]);
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
