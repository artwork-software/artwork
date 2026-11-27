<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\FreeDayAfterWorkOnDateCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * NV Bühne: Wer am 24. Dezember arbeitet, hat am folgenden 1. Mai dienstfrei.
 */
final class FreeDayAfterWorkOnDateCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private FreeDayAfterWorkOnDateCheck $check;
    private Carbon $christmasEve;
    private Carbon $mayDay;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new FreeDayAfterWorkOnDateCheck();
        $year = Carbon::now()->year + 1;
        $this->christmasEve = Carbon::create($year, 12, 24)->startOfDay();
        $this->mayDay = Carbon::create($year + 1, 5, 1)->startOfDay();
    }

    private function rule(?array $config = ['worked' => '12-24', 'free' => '05-01']): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'freeDayAfterWorkOnDate',
            'individual_number_value' => 0,
            'config' => $config,
            'is_active' => true,
        ]);
    }

    private function violationsFor(ShiftRule $rule, User $user): Collection
    {
        return $this->check->check(
            $rule,
            $user,
            $this->mayDay->copy()->subDays(3),
            $this->mayDay->copy()->addDays(3)
        );
    }

    #[Test]
    public function shift_on_may_day_after_work_on_christmas_eve_is_a_violation(): void
    {
        $user = User::factory()->create();
        $this->shiftFor($user, $this->christmasEve);
        $shift = $this->shiftFor($user, $this->mayDay);

        $violations = $this->violationsFor($this->rule(), $user);

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($shift->id, $violation->shift_id);
        $this->assertSame($this->mayDay->toDateString(), $violation->violation_date->toDateString());
        $this->assertSame($this->christmasEve->toDateString(), $violation->violation_data['worked_date']);
        $this->assertSame($this->mayDay->toDateString(), $violation->violation_data['free_date']);
    }

    #[Test]
    public function no_violation_without_work_on_christmas_eve(): void
    {
        $user = User::factory()->create();
        $this->shiftFor($user, $this->christmasEve->copy()->subDay());
        $this->shiftFor($user, $this->mayDay);

        $this->assertCount(0, $this->violationsFor($this->rule(), $user));
    }

    #[Test]
    public function no_violation_when_may_day_is_free(): void
    {
        $user = User::factory()->create();
        $this->shiftFor($user, $this->christmasEve);
        $this->shiftFor($user, $this->mayDay->copy()->addDay());

        $this->assertCount(0, $this->violationsFor($this->rule(), $user));
    }

    #[Test]
    public function an_individual_time_on_the_free_day_counts_as_work(): void
    {
        $user = User::factory()->create();
        $this->shiftFor($user, $this->christmasEve);
        $this->individualTimeFor($user, $this->mayDay);

        $violations = $this->violationsFor($this->rule(), $user);

        $this->assertCount(1, $violations);
        $this->assertNull($violations->first()->shift_id);
    }

    #[Test]
    public function range_without_the_free_day_or_rule_without_dates_yields_nothing(): void
    {
        $user = User::factory()->create();
        $this->shiftFor($user, $this->christmasEve);
        $this->shiftFor($user, $this->mayDay);

        $outside = $this->check->check(
            $this->rule(),
            $user,
            $this->mayDay->copy()->addDays(2),
            $this->mayDay->copy()->addDays(9)
        );
        $this->assertCount(0, $outside);

        $this->assertCount(0, $this->violationsFor($this->rule(null), $user));
    }

    #[Test]
    public function free_day_later_in_the_same_year_uses_the_worked_day_of_that_year(): void
    {
        $user = User::factory()->create();
        $worked = Carbon::create($this->mayDay->year, 3, 10)->startOfDay();
        $this->shiftFor($user, $worked);
        $this->shiftFor($user, $this->mayDay);

        $violations = $this->violationsFor($this->rule(['worked' => '03-10', 'free' => '05-01']), $user);

        $this->assertCount(1, $violations);
        $this->assertSame($worked->toDateString(), $violations->first()->violation_data['worked_date']);
    }
}
