<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\FreeDayAnnouncementLeadTimeCheck;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ankündigungsfrist freier Tage (NV Bühne § 66 Abs. 7): gewährte Ersatzfreitage mit zu wenig Vorlauf.
 */
final class FreeDayAnnouncementLeadTimeCheckTest extends TestCase
{
    private FreeDayAnnouncementLeadTimeCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new FreeDayAnnouncementLeadTimeCheck();
    }

    private function rule(float $days = 14.0): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'freeDayAnnouncementLeadTime',
            'individual_number_value' => $days,
            'is_active' => true,
        ]);
    }

    private function dayOff(
        User $user,
        Carbon $freeDay,
        ?Carbon $grantedAt,
        float $value = 1.0,
        ?string $period = null
    ): void {
        CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => $value,
            'half_day_period' => $period,
            'granted_date' => $grantedAt !== null ? $freeDay->toDateString() : null,
            'granted_at' => $grantedAt,
            'deadline' => $freeDay->copy()->addDays(30)->toDateString(),
        ]);
    }

    private function violationsFor(ShiftRule $rule, User $user): Collection
    {
        $today = Carbon::now()->startOfDay();

        return $this->check->check($rule, $user, $today->copy(), $today->copy()->addDays(60));
    }

    #[Test]
    public function day_off_granted_with_too_little_lead_time_is_a_violation(): void
    {
        $user = User::factory()->create();
        $freeDay = Carbon::now()->startOfDay()->addDays(5);
        $this->dayOff($user, $freeDay, Carbon::now());

        $violations = $this->violationsFor($this->rule(), $user);

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertNull($violation->shift_id);
        $this->assertSame($freeDay->toDateString(), $violation->violation_date->toDateString());
        $this->assertSame(5, $violation->violation_data['lead_days']);
        $this->assertSame(14, $violation->violation_data['min_required']);
    }

    #[Test]
    public function day_off_granted_exactly_on_time_is_fine(): void
    {
        $user = User::factory()->create();
        $this->dayOff($user, Carbon::now()->startOfDay()->addDays(14), Carbon::now());

        $this->assertCount(0, $this->violationsFor($this->rule(), $user));
    }

    #[Test]
    public function half_days_are_checked_and_two_halves_of_one_day_give_one_violation(): void
    {
        $user = User::factory()->create();
        $freeDay = Carbon::now()->startOfDay()->addDays(3);
        $this->dayOff($user, $freeDay, Carbon::now(), 0.5, 'morning');
        $this->dayOff($user, $freeDay, Carbon::now(), 0.5, 'afternoon');

        $violations = $this->violationsFor($this->rule(), $user);

        $this->assertCount(1, $violations);
        $this->assertEquals(1.0, $violations->first()->violation_data['value']);
    }

    #[Test]
    public function open_days_off_without_a_granted_day_are_ignored(): void
    {
        $user = User::factory()->create();
        $this->dayOff($user, Carbon::now()->startOfDay()->addDays(3), null);

        $this->assertCount(0, $this->violationsFor($this->rule(), $user));
    }

    #[Test]
    public function other_persons_are_not_affected(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->dayOff($other, Carbon::now()->startOfDay()->addDays(3), Carbon::now());

        $this->assertCount(0, $this->violationsFor($this->rule(), $user));
    }

    private function plannedFreeDay(User $user, Carbon $freeDay, Carbon $createdAt, bool $fullDay = true): void
    {
        $vacation = new Vacation([
            'date' => $freeDay->toDateString(),
            'full_day' => $fullDay,
            'day_part' => $fullDay ? null : 'morning',
            'type' => 'FREE_WORK',
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
        ]);
        $vacation->created_at = $createdAt;
        $vacation->updated_at = $createdAt;
        $vacation->save();
    }

    #[Test]
    public function plannedFreeDayWithTooLittleLeadTimeIsAViolation(): void
    {
        $user = User::factory()->create();
        $today = Carbon::now()->startOfDay();
        $this->plannedFreeDay($user, $today->copy()->addDays(5), $today->copy());

        $violations = $this->violationsFor($this->rule(14.0), $user);

        $this->assertCount(1, $violations);
    }

    #[Test]
    public function plannedFreeDayAnnouncedInTimeIsNoViolation(): void
    {
        $user = User::factory()->create();
        $today = Carbon::now()->startOfDay();
        $this->plannedFreeDay($user, $today->copy()->addDays(5), $today->copy()->subDays(14), false);

        $this->assertCount(0, $this->violationsFor($this->rule(14.0), $user));
    }

    #[Test]
    public function plannedFreeDayAndCompensationDayOnSameDateGiveOneViolation(): void
    {
        $user = User::factory()->create();
        $today = Carbon::now()->startOfDay();
        $freeDay = $today->copy()->addDays(5);
        $this->plannedFreeDay($user, $freeDay, $today->copy()->subDays(30), false);
        $this->dayOff($user, $freeDay, $today->copy(), 0.5, 'afternoon');

        $this->assertCount(1, $this->violationsFor($this->rule(14.0), $user));
    }

    #[Test]
    public function an_older_day_status_switched_to_free_counts_from_the_switch(): void
    {
        $user = User::factory()->create();
        $today = Carbon::now()->startOfDay();
        $vacation = new Vacation([
            'date' => $today->copy()->addDays(5)->toDateString(),
            'full_day' => true,
            'type' => 'NOT_AVAILABLE',
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
        ]);
        $vacation->created_at = $today->copy()->subDays(60);
        $vacation->updated_at = $today->copy()->subDays(60);
        $vacation->save();

        // Zwei Monate später stellt die Planung den Tag auf "Frei" um
        $vacation->update(['type' => 'FREE_WORK']);

        $this->assertNotNull($vacation->fresh()->status_changed_at);
        $this->assertCount(1, $this->violationsFor($this->rule(14.0), $user));
    }
}
