<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftGroup;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\RestTimeBetweenShiftGroupsCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RestTimeBetweenShiftGroupsCheckTest extends TestCase
{
    private RestTimeBetweenShiftGroupsCheck $check;
    private Carbon $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new RestTimeBetweenShiftGroupsCheck();
        $this->date = Carbon::now()->startOfDay()->addDays(5);
    }

    private function rule(float $minRestHours = 11.0): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'restTimeBetweenShiftGroups',
            'individual_number_value' => $minRestHours,
            'is_active' => true,
        ]);
    }

    private function shift(User $user, string $start, string $end, ?int $groupId): Shift
    {
        $shift = Shift::factory()->create([
            'start_date' => $this->date->toDateString(),
            'end_date' => $this->date->toDateString(),
            'start' => $start,
            'end' => $end,
            'shift_group_id' => $groupId,
        ]);

        $sq = ShiftQualification::factory()->create();
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => $sq->id,
            'shift_count' => 1,
        ]);

        return $shift;
    }

    #[Test]
    public function violation_when_two_different_groups_are_too_close(): void
    {
        $user = User::factory()->create();
        $groupA = ShiftGroup::create(['name' => 'Early']);
        $groupB = ShiftGroup::create(['name' => 'Late']);

        // 08:00-12:00 and 13:00-17:00 -> only 1h rest, rule requires 11h.
        $this->shift($user, '08:00:00', '12:00:00', $groupA->id);
        $this->shift($user, '13:00:00', '17:00:00', $groupB->id);

        $violations = $this->check->check($this->rule(11.0), $user, $this->date->copy(), $this->date->copy());

        $this->assertCount(1, $violations);
    }

    #[Test]
    public function no_violation_when_groups_are_identical(): void
    {
        $user = User::factory()->create();
        $group = ShiftGroup::create(['name' => 'Same']);

        $this->shift($user, '08:00:00', '12:00:00', $group->id);
        $this->shift($user, '13:00:00', '17:00:00', $group->id);

        $violations = $this->check->check($this->rule(11.0), $user, $this->date->copy(), $this->date->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function no_violation_when_rest_gap_is_sufficient(): void
    {
        $user = User::factory()->create();
        $groupA = ShiftGroup::create(['name' => 'Early']);
        $groupB = ShiftGroup::create(['name' => 'Late']);

        // 08:00-10:00 and 22:00-23:00 -> 12h rest, rule requires 11h.
        $this->shift($user, '08:00:00', '10:00:00', $groupA->id);
        $this->shift($user, '22:00:00', '23:00:00', $groupB->id);

        $violations = $this->check->check($this->rule(11.0), $user, $this->date->copy(), $this->date->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function ignores_shifts_without_a_group(): void
    {
        $user = User::factory()->create();
        $group = ShiftGroup::create(['name' => 'Early']);

        // One grouped shift + one ungrouped shift close together -> no comparison, no violation.
        $this->shift($user, '08:00:00', '12:00:00', $group->id);
        $this->shift($user, '13:00:00', '17:00:00', null);

        $violations = $this->check->check($this->rule(11.0), $user, $this->date->copy(), $this->date->copy());

        $this->assertCount(0, $violations);
    }
}
