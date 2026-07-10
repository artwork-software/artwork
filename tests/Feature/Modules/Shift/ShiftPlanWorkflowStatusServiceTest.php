<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\ShiftPlanWorkflowStatusService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * KW-Kachel-Status im Schichtplan (Dienstplanfreigabe):
 * requested (blau) / committed (grün) / attention (gelb) pro Person und Kalenderwoche.
 */
final class ShiftPlanWorkflowStatusServiceTest extends FeatureTestCase
{
    private const DAY = '2026-07-20'; // Montag

    private ShiftPlanWorkflowStatusService $service;

    private ShiftQualification $qualification;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShiftPlanWorkflowStatusService::class);
        $this->qualification = ShiftQualification::factory()->create();
    }

    private function weekKey(string $date = self::DAY): string
    {
        return ltrim(Carbon::parse($date)->format('W'), '0');
    }

    private function createShiftWithUser(array $shiftAttributes = [], ?User $user = null): array
    {
        // Erst zuweisen, dann Workflow-Status leise setzen: attach() auf eine bereits
        // festgeschriebene Schicht würde (korrekt) einen CommittedShiftChange erzeugen
        // und jede Woche sofort auf "attention" kippen.
        $shift = Shift::factory()->create([
            'start_date' => self::DAY,
            'end_date' => self::DAY,
            'start' => '10:00',
            'end' => '18:00',
            'is_committed' => false,
            'in_workflow' => false,
        ]);

        $user ??= User::factory()->create();
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => $this->qualification->id,
        ]);

        if ($shiftAttributes !== []) {
            $shift->forceFill($shiftAttributes)->saveQuietly();
        }

        return [$shift->fresh(), $user];
    }

    private function statusFor(User $user): ?string
    {
        $start = Carbon::parse(self::DAY)->startOfWeek();
        $end = Carbon::parse(self::DAY)->endOfWeek();
        $map = $this->service->computeForDateRange($start, $end);

        return $map['user'][$user->id][$this->weekKey()] ?? null;
    }

    #[Test]
    public function shift_in_workflow_marks_week_as_requested(): void
    {
        [, $user] = $this->createShiftWithUser(['in_workflow' => true]);

        $this->assertSame('requested', $this->statusFor($user));
    }

    #[Test]
    public function week_is_committed_only_when_all_shifts_of_user_are_committed(): void
    {
        [, $user] = $this->createShiftWithUser(['is_committed' => true]);

        $this->assertSame('committed', $this->statusFor($user));

        // Zweite, nicht festgeschriebene Schicht in derselben KW → kein Grün mehr
        $this->createShiftWithUser([], $user);

        $this->assertNull($this->statusFor($user));
    }

    #[Test]
    public function requested_wins_over_committed_within_a_week(): void
    {
        [, $user] = $this->createShiftWithUser(['is_committed' => true]);
        $this->createShiftWithUser(['in_workflow' => true], $user);

        $this->assertSame('requested', $this->statusFor($user));
    }

    #[Test]
    public function unacknowledged_change_marks_assigned_workers_with_attention(): void
    {
        [$shift, $user] = $this->createShiftWithUser(['is_committed' => true]);

        CommittedShiftChange::create([
            'craft_id' => $shift->craft_id ?? Craft::factory()->create()->id,
            'shift_id' => $shift->id,
            'subject_type' => \Artwork\Modules\Shift\Models\Shift::class,
            'subject_id' => $shift->id,
            'change_type' => 'updated',
            'field_changes' => ['start' => ['old' => '09:00', 'new' => '10:00']],
            'changed_by_user_id' => User::factory()->create()->id,
            'changed_at' => Carbon::parse(self::DAY)->setTime(12, 0),
        ]);

        $this->assertSame('attention', $this->statusFor($user));
    }

    #[Test]
    public function acknowledged_change_returns_week_to_committed(): void
    {
        [$shift, $user] = $this->createShiftWithUser(['is_committed' => true]);

        CommittedShiftChange::create([
            'craft_id' => $shift->craft_id ?? Craft::factory()->create()->id,
            'shift_id' => $shift->id,
            'subject_type' => \Artwork\Modules\Shift\Models\Shift::class,
            'subject_id' => $shift->id,
            'change_type' => 'updated',
            'field_changes' => [],
            'changed_by_user_id' => User::factory()->create()->id,
            'changed_at' => Carbon::parse(self::DAY)->setTime(12, 0),
            'acknowledged_at' => Carbon::parse(self::DAY)->setTime(13, 0),
            'acknowledged_by_user_id' => User::factory()->create()->id,
        ]);

        $this->assertSame('committed', $this->statusFor($user));
    }

    #[Test]
    public function directly_affected_user_gets_attention_even_when_no_longer_assigned(): void
    {
        [$shift, $user] = $this->createShiftWithUser(['is_committed' => true]);

        $removedUser = User::factory()->create();

        CommittedShiftChange::create([
            'craft_id' => $shift->craft_id ?? Craft::factory()->create()->id,
            'shift_id' => $shift->id,
            'subject_type' => \Artwork\Modules\Shift\Models\Shift::class,
            'subject_id' => $shift->id,
            'change_type' => 'worker_removed',
            'field_changes' => [],
            'affected_user_type' => User::class,
            'affected_user_id' => $removedUser->id,
            'changed_by_user_id' => User::factory()->create()->id,
            'changed_at' => Carbon::parse(self::DAY)->setTime(12, 0),
        ]);

        $this->assertSame('attention', $this->statusFor($removedUser));
        // Die verbliebene Person ist nicht direkt betroffen → bleibt grün
        $this->assertSame('committed', $this->statusFor($user));
    }

    #[Test]
    public function unavailable_worker_on_committed_shift_gets_attention(): void
    {
        [, $user] = $this->createShiftWithUser(['is_committed' => true]);

        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => self::DAY,
            'full_day' => true,
            'is_series' => false,
            'type' => VacationType::NOT_AVAILABLE,
        ]);

        $this->assertSame('attention', $this->statusFor($user));
    }

    #[Test]
    public function unavailable_worker_on_uncommitted_shift_gets_no_attention(): void
    {
        [, $user] = $this->createShiftWithUser();

        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => self::DAY,
            'full_day' => true,
            'is_series' => false,
            'type' => VacationType::NOT_AVAILABLE,
        ]);

        $this->assertNull($this->statusFor($user));
    }

    #[Test]
    public function week_without_workflow_state_has_no_status(): void
    {
        [, $user] = $this->createShiftWithUser();

        $this->assertNull($this->statusFor($user));
    }

    #[Test]
    public function single_worker_filter_returns_same_status(): void
    {
        [, $user] = $this->createShiftWithUser(['in_workflow' => true]);

        $start = Carbon::parse(self::DAY)->startOfWeek();
        $end = Carbon::parse(self::DAY)->endOfWeek();
        $map = $this->service->computeForDateRange($start, $end, User::class, $user->id);

        $this->assertSame('requested', $map['user'][$user->id][$this->weekKey()] ?? null);
    }
}
