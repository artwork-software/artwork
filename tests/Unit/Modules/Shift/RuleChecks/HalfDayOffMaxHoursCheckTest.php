<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\ShiftGroup;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\HalfDayOffMaxHoursCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * Arbeitszeit in der anderen Tageshälfte eines halben freien Tages (NV Bühne: höchstens 4 Stunden, bei
 * Endproben und Aufführungen 5).
 */
final class HalfDayOffMaxHoursCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private HalfDayOffMaxHoursCheck $check;
    private Carbon $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new HalfDayOffMaxHoursCheck();
        $this->date = Carbon::now()->startOfDay()->addDays(10);
    }

    private function rule(float $hours = 4.0, ?array $config = null): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'halfDayOffMaxHours',
            'individual_number_value' => $hours,
            'config' => $config,
            'is_active' => true,
        ]);
    }

    private function grantedHalf(User $user, string $period): void
    {
        CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => 0.5,
            'half_day_period' => $period,
            'granted_date' => $this->date->toDateString(),
            'granted_at' => Carbon::now(),
            'deadline' => $this->date->copy()->addDays(30)->toDateString(),
        ]);
    }

    private function violationsFor(ShiftRule $rule, User $user): Collection
    {
        return $this->check->check($rule, $user, $this->date->copy(), $this->date->copy());
    }

    #[Test]
    public function free_morning_with_more_than_four_hours_in_the_afternoon_is_a_violation(): void
    {
        $user = User::factory()->create();
        $this->grantedHalf($user, 'morning');
        $shift = $this->shiftFor($user, $this->date, '14:00:00', '19:00:00');

        $violations = $this->violationsFor($this->rule(), $user);

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($shift->id, $violation->shift_id);
        $this->assertEquals(5.0, $violation->violation_data['planned_hours']);
        $this->assertEquals(4.0, $violation->violation_data['max_allowed']);
        $this->assertSame('morning', $violation->violation_data['half_day_period']);
        $this->assertFalse($violation->violation_data['extended']);
    }

    #[Test]
    public function exactly_four_hours_are_fine(): void
    {
        $user = User::factory()->create();
        $this->grantedHalf($user, 'morning');
        $this->shiftFor($user, $this->date, '14:00:00', '18:00:00');

        $this->assertCount(0, $this->violationsFor($this->rule(), $user));
    }

    #[Test]
    public function free_afternoon_counts_the_work_before_the_boundary(): void
    {
        $user = User::factory()->create();
        $this->grantedHalf($user, 'afternoon');
        $this->shiftFor($user, $this->date, '08:00:00', '14:00:00');

        $violations = $this->violationsFor($this->rule(), $user);

        $this->assertCount(1, $violations);
        $this->assertEquals(6.0, $violations->first()->violation_data['planned_hours']);
        $this->assertSame('afternoon', $violations->first()->violation_data['half_day_period']);
    }

    #[Test]
    public function break_is_deducted_when_the_shift_lies_completely_in_the_working_half(): void
    {
        $user = User::factory()->create();
        $this->grantedHalf($user, 'morning');
        // 14:00–18:30 mit 30 Minuten Pause = 4 Stunden netto
        $this->shiftFor($user, $this->date, '14:00:00', '18:30:00', ['break_minutes' => 30]);

        $this->assertCount(0, $this->violationsFor($this->rule(), $user));
    }

    #[Test]
    public function individual_times_count_as_well(): void
    {
        $user = User::factory()->create();
        $this->grantedHalf($user, 'morning');
        $this->shiftFor($user, $this->date, '14:00:00', '17:00:00');
        $this->individualTimeFor($user, $this->date, '17:00', '19:00');

        $violations = $this->violationsFor($this->rule(), $user);

        $this->assertCount(1, $violations);
        $this->assertEquals(5.0, $violations->first()->violation_data['planned_hours']);
    }

    #[Test]
    public function extended_limit_applies_for_configured_shift_groups(): void
    {
        $user = User::factory()->create();
        $group = ShiftGroup::create(['name' => 'Vorstellung']);
        $this->grantedHalf($user, 'morning');
        $this->shiftFor($user, $this->date, '18:00:00', '23:00:00', ['shift_group_id' => $group->id]);
        $rule = $this->rule(4.0, ['extended_hours' => 5.0, 'extended_shift_group_ids' => [$group->id]]);

        $this->assertCount(0, $this->violationsFor($rule, $user));

        // Ohne Zuordnung der Schichtgruppe gilt der normale Wert
        $this->assertCount(1, $this->violationsFor($this->rule(4.0, ['extended_hours' => 5.0]), $user));
    }

    #[Test]
    public function extended_limit_can_be_exceeded_too(): void
    {
        $user = User::factory()->create();
        $group = ShiftGroup::create(['name' => 'Endprobe']);
        $this->grantedHalf($user, 'morning');
        $this->shiftFor($user, $this->date, '17:00:00', '23:00:00', ['shift_group_id' => $group->id]);
        $rule = $this->rule(4.0, ['extended_hours' => 5.0, 'extended_shift_group_ids' => [$group->id]]);

        $violations = $this->violationsFor($rule, $user);

        $this->assertCount(1, $violations);
        $this->assertEquals(5.0, $violations->first()->violation_data['max_allowed']);
        $this->assertTrue($violations->first()->violation_data['extended']);
    }

    #[Test]
    public function boundary_is_configurable(): void
    {
        $user = User::factory()->create();
        $this->grantedHalf($user, 'morning');
        // 13:00–18:00: bei Grenze 14:00 zählen 4 Stunden, bei Grenze 13:00 zählen 5
        $this->shiftFor($user, $this->date, '13:00:00', '18:00:00');

        $this->assertCount(0, $this->violationsFor($this->rule(), $user));
        $this->assertCount(1, $this->violationsFor($this->rule(4.0, ['boundary' => '13:00']), $user));
    }

    #[Test]
    public function days_without_half_day_off_or_with_both_halves_are_ignored(): void
    {
        $user = User::factory()->create();
        $this->shiftFor($user, $this->date, '08:00:00', '20:00:00');

        $this->assertCount(0, $this->violationsFor($this->rule(), $user));

        $this->grantedHalf($user, 'morning');
        $this->grantedHalf($user, 'afternoon');

        $this->assertCount(0, $this->violationsFor($this->rule(), $user));
    }

    #[Test]
    public function planned_half_day_off_from_the_day_status_is_checked_as_well(): void
    {
        $user = User::factory()->create();
        Vacation::create([
            'date' => $this->date->toDateString(),
            'full_day' => false,
            'day_part' => 'morning',
            'type' => 'FREE_WORK',
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
        ]);
        $this->shiftFor($user, $this->date, '14:00:00', '19:30:00');

        $violations = $this->violationsFor($this->rule(), $user);

        $this->assertCount(1, $violations);
        $this->assertEquals(5.5, $violations->first()->violation_data['planned_hours']);
    }
}
