<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftGroup;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\RestTimeBetweenShiftsOnDayCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * Geteilter Dienst (NV Bühne § 65 Abs. 2): vier Stunden zwischen zwei Arbeitsabschnitten am selben Tag.
 */
final class RestTimeBetweenShiftsOnDayCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private RestTimeBetweenShiftsOnDayCheck $check;
    private Carbon $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new RestTimeBetweenShiftsOnDayCheck();
        $this->date = Carbon::now()->startOfDay()->addDays(5);
    }

    private function rule(float $hours = 4.0, ?array $config = null): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'restTimeBetweenShiftsOnDay',
            'individual_number_value' => $hours,
            'config' => $config,
            'is_active' => true,
        ]);
    }

    private function violationsFor(ShiftRule $rule, User $user): Collection
    {
        return $this->check->check($rule, $user, $this->date->copy(), $this->date->copy());
    }

    #[Test]
    public function gap_shorter_than_the_minimum_is_a_violation_without_any_shift_groups(): void
    {
        $user = User::factory()->create();
        $this->shiftFor($user, $this->date, '08:00:00', '12:00:00');
        $second = $this->shiftFor($user, $this->date, '14:00:00', '18:00:00');

        $violations = $this->violationsFor($this->rule(), $user);

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($second->id, $violation->shift_id);
        $this->assertEquals(2.0, $violation->violation_data['rest_hours']);
        $this->assertEquals(4.0, $violation->violation_data['min_required']);
    }

    #[Test]
    public function gap_of_exactly_the_minimum_is_fine(): void
    {
        $user = User::factory()->create();
        $this->shiftFor($user, $this->date, '08:00:00', '12:00:00');
        $this->shiftFor($user, $this->date, '16:00:00', '20:00:00');

        $this->assertCount(0, $this->violationsFor($this->rule(), $user));
    }

    #[Test]
    public function adjacent_or_overlapping_duties_are_not_a_split_duty(): void
    {
        $user = User::factory()->create();
        $this->shiftFor($user, $this->date, '08:00:00', '12:00:00');
        $this->shiftFor($user, $this->date, '12:00:00', '16:00:00');
        $this->shiftFor($user, $this->date, '15:00:00', '18:00:00');

        $this->assertCount(0, $this->violationsFor($this->rule(), $user));
    }

    #[Test]
    public function gap_is_measured_from_the_latest_end_of_overlapping_duties(): void
    {
        $user = User::factory()->create();
        // 08–14 überdeckt 09–10: die Lücke bis 16 Uhr beträgt 2 Stunden, nicht 6
        $this->shiftFor($user, $this->date, '08:00:00', '14:00:00');
        $this->shiftFor($user, $this->date, '09:00:00', '10:00:00');
        $this->shiftFor($user, $this->date, '16:00:00', '18:00:00');

        $violations = $this->violationsFor($this->rule(), $user);

        $this->assertCount(1, $violations);
        $this->assertEquals(2.0, $violations->first()->violation_data['rest_hours']);
    }

    #[Test]
    public function excluded_shift_groups_are_not_checked(): void
    {
        $user = User::factory()->create();
        $group = ShiftGroup::create(['name' => 'Endprobe']);
        $this->shiftFor($user, $this->date, '08:00:00', '12:00:00');
        $this->shiftFor($user, $this->date, '14:00:00', '22:00:00', ['shift_group_id' => $group->id]);

        $this->assertCount(1, $this->violationsFor($this->rule(), $user));
        $this->assertCount(
            0,
            $this->violationsFor($this->rule(4.0, ['excluded_shift_group_ids' => [$group->id]]), $user)
        );
    }

    #[Test]
    public function individual_times_count_as_duties(): void
    {
        $user = User::factory()->create();
        $shift = $this->shiftFor($user, $this->date, '08:00:00', '12:00:00');
        $this->individualTimeFor($user, $this->date, '13:00', '15:00');

        $violations = $this->violationsFor($this->rule(), $user);

        $this->assertCount(1, $violations);
        // Folgt eine individuelle Zeit, hängt der Verstoß an der vorangehenden Schicht
        $this->assertSame($shift->id, $violations->first()->shift_id);
    }

    #[Test]
    public function duties_on_different_days_are_not_compared(): void
    {
        $user = User::factory()->create();
        $this->shiftFor($user, $this->date, '18:00:00', '23:00:00');
        $this->shiftFor($user, $this->date->copy()->addDay(), '01:00:00', '05:00:00');

        $violations = $this->check->check($this->rule(), $user, $this->date->copy(), $this->date->copy()->addDay());

        $this->assertCount(0, $violations);
    }
}
