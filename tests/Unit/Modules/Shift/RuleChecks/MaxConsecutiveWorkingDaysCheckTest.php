<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\MaxConsecutiveWorkingDaysCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

final class MaxConsecutiveWorkingDaysCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private MaxConsecutiveWorkingDaysCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new MaxConsecutiveWorkingDaysCheck();
    }

    private function rule(int $days = 3): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'maxConsecWorkingDays',
            'individual_number_value' => $days,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function violation_on_the_day_the_streak_exceeds_the_maximum(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        for ($i = 0; $i < 4; $i++) {
            $this->shiftFor($user, $monday->copy()->addDays($i));
        }

        $violations = $this->check->check($this->rule(3), $user, $monday->copy(), $monday->copy()->addDays(3));

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($monday->copy()->addDays(3)->toDateString(), $violation->violation_date->toDateString());
        $this->assertSame(4, (int) $violation->violation_data['consecutive_days']);
        $this->assertSame(3, (int) $violation->violation_data['max_allowed']);
    }

    #[Test]
    public function streak_before_the_checked_range_is_taken_into_account(): void
    {
        $user = User::factory()->create();
        $wednesday = $this->futureWeekday(Carbon::WEDNESDAY);
        // Mo + Di liegen VOR dem Prüfzeitraum, Mi + Do innerhalb
        for ($i = -2; $i <= 1; $i++) {
            $this->shiftFor($user, $wednesday->copy()->addDays($i));
        }

        $violations = $this->check->check($this->rule(3), $user, $wednesday->copy(), $wednesday->copy()->addDay());

        $this->assertCount(1, $violations);
        $this->assertSame($wednesday->copy()->addDay()->toDateString(), $violations->first()->violation_date->toDateString());
    }

    #[Test]
    public function a_free_day_resets_the_streak(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        // Mo, Di, Mi, (Do frei), Fr, Sa, So -> max. 3 in Folge
        foreach ([0, 1, 2, 4, 5, 6] as $offset) {
            $this->shiftFor($user, $monday->copy()->addDays($offset));
        }

        $violations = $this->check->check($this->rule(3), $user, $monday->copy(), $monday->copy()->addDays(6));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function pivot_dates_of_the_person_override_the_shift_dates(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        // Mo–Mi Schichten; die Donnerstagsschicht ist laut Pivot für die Person auf Freitag verschoben
        for ($i = 0; $i < 3; $i++) {
            $this->shiftFor($user, $monday->copy()->addDays($i));
        }
        $thursdayShift = $this->shiftFor($user, $monday->copy()->addDays(3));
        $friday = $monday->copy()->addDays(4);
        $this->setPivotTimes($thursdayShift, $user, '08:00:00', '16:00:00', $friday, $friday);

        $violations = $this->check->check($this->rule(3), $user, $monday->copy(), $monday->copy()->addDays(4));

        // Do ist frei -> Serie Mo–Mi (3) bricht ab, Fr beginnt neu: kein Verstoß
        $this->assertCount(0, $violations);
    }
}
