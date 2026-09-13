<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Block 2c: nutzerlesbare Backend-Meldungen mit Handlungsempfehlung und
 * Funktionsname im Einsatzplan-Payload.
 */
final class ShiftUserFacingMessagesTest extends FeatureTestCase
{
    #[Test]
    public function assigningWithoutPlannerPermissionExplainsTheMissingPermission(): void
    {
        $this->actingAs(User::factory()->create());
        $shift = Shift::factory()->create();
        $qualification = ShiftQualification::factory()->create();

        $response = $this->postJson(route('shift.assignUserByType', $shift), [
            'userId' => User::factory()->create()->id,
            'userType' => 0,
            'shiftQualificationId' => $qualification->id,
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', __('You need the permission "Plan shifts" for this.'));
    }

    #[Test]
    public function compensationDaysMustBeEnteredInHalfDays(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $violation = $this->activeViolation();

        $response = $this->put(
            route('shift-rule-violations.process', ['violation' => $violation->id]),
            [
                'compensation_days' => 1.25,
                'compensation_deadline' => Carbon::now()->addDays(20)->toDateString(),
                'compensation_reason' => 'Ausgleich',
                'for_holiday' => false,
            ]
        );

        $response->assertSessionHasErrors([
            'compensation_days' => __('Compensation days are entered in half days (0.5; 1; 1.5 …).'),
        ]);
    }

    #[Test]
    public function processingAnIgnoredViolationPointsToTheHistory(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $violation = $this->activeViolation();
        $violation->update(['status' => 'ignored']);

        $response = $this->put(
            route('shift-rule-violations.process', ['violation' => $violation->id]),
            [
                'compensation_days' => 1,
                'compensation_deadline' => Carbon::now()->addDays(20)->toDateString(),
                'compensation_reason' => 'Ausgleich',
                'for_holiday' => false,
            ]
        );

        $response->assertSessionHas(
            'error',
            __('This violation has already been processed or ignored. Open it from the history to see details.')
        );
    }

    #[Test]
    public function workflowUserStoreExplainsEmptyAndUnknownSelections(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('shift.settings.update.shift-commit-workflow-users'), ['users' => [0]])
            ->assertSessionHasErrors(['users' => __('Select at least one person.')]);

        $this->patch(route('shift.settings.update.shift-commit-workflow-users'), ['users' => [999999]])
            ->assertSessionHasErrors([
                'users' => __('One of the selected people no longer exists. Please reload the list.'),
            ]);
    }

    #[Test]
    public function daysWithDataPayloadContainsTheQualificationNameOnThePivot(): void
    {
        $user = User::factory()->create();
        $qualification = ShiftQualification::factory()->create(['name' => 'Operator*in']);

        $shift = Shift::factory()->create([
            'event_id' => null,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
            'start' => '10:00:00',
            'end' => '18:00:00',
            'break_minutes' => 30,
        ]);
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => $qualification->id,
            'shift_count' => 1,
        ]);

        $days = app(EventService::class)->getDaysWithEventsAndTotalPlannedWorkingHours(
            $user->id,
            'user',
            Carbon::parse('2026-08-10'),
            Carbon::parse('2026-08-16')
        );

        $workers = $days['2026-08-12']['shifts'][0]['workers'];
        $this->assertCount(1, $workers);
        $this->assertSame('user', $workers[0]->type);
        $this->assertSame('Operator*in', $workers[0]->pivot->shift_qualification_name);
        $this->assertSame(30, (int) $days['2026-08-12']['shifts'][0]['break_minutes']);
    }

    private function activeViolation(): ShiftRuleViolation
    {
        $rule = ShiftRule::factory()->create([
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'default_compensation_deadline_days' => 30,
        ]);

        return ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'user_id' => User::factory()->create()->id,
            'shift_id' => null,
            'violation_date' => Carbon::now()->addDays(3)->toDateString(),
            'status' => 'active',
            'is_manual' => false,
        ]);
    }
}
